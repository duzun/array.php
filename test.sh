#!/bin/sh

workdir=$(pwd)
myname=$(basename "$0")

PHPUNIT_VERSION_84=^12.0
PHPUNIT_VERSION_83=^12.0
PHPUNIT_VERSION_82=^11.0
PHPUNIT_VERSION_81=^10.2
PHPUNIT_VERSION_80=^9.6
PHPUNIT_VERSION_74=^9.6
PHPUNIT_VERSION_73=^9.6
PHPUNIT_VERSION_72=^8.5
PHPUNIT_VERSION_71=^7.5

# These versions are not supported any more, but we keep them here for history.
PHPUNIT_VERSION_70=^6.5 #^5.7
PHPUNIT_VERSION_56=^5.7
PHPUNIT_VERSION_55=^4.8
PHPUNIT_VERSION_54=^4.8
PHPUNIT_VERSION_53=^4.8

DOCKER_VERSION_54=cli
DOCKER_VERSION_53=cli

# flags to pass to install
flags="--prefer-dist --no-interaction --optimize-autoloader --no-progress"

usage() {
    cat <<EOH
    $myname <php.ver>|"all"|"edge" [w] {<phpunit_options>}
    $myname <php.ver> [sh|bash]
    $myname -h|--help|?
Eg.
    # Run unit-tests in PHP 8.4 with watch
    $myname 8.4 w --filter ArrayClass
EOH
}

var() {
    eval "echo \${$1:-$2}"
}

ver_num() {
    echo "$1" | cut -d- -f1 | sed 's/\.//g'
}

composer_php_vers() {
    export PHP_INI_SCAN_DIR=/dev/null
    composer show -s --format="json" 2>/dev/null \
        | grep -Po '"php":\s*"\K[^"]+' \
        | tr '|' '\n' \
        | sed 's/^[^0-9]*//' \
        | sort -V
}

main_in_docker() {
    # shellcheck disable=SC3043
    local watch c
    install_dev "$1" || return $?
    shift

    case $1 in
    s | script)
        shift
        $composer run "$@"
        return $?
        ;;
    v | vendor)
        shift
        bin=$1
        shift
        ./vendor/bin/$bin "$@"
        return $?
        ;;
    w)
        watch=1
        shift
        if ! command -v inotifywait >/dev/null; then
            apk -U add inotify-tools
        fi
        ;;
    esac


    echo
    echo
    echo " - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -"
    echo

    [ -s "$workdir/tests/phpunit.xml" ] && c="-c $workdir/tests/phpunit.xml"
    phpunit tests/ $c "$@"

    if [ -n "$watch" ]; then
        watchnrun "$workdir" \
            phpunit tests/ $c "$@"
    fi

    ( cd "$workdir" && [ -s 'composer.json.lock' ] && mv -f -- composer.json.lock composer.json )
}

# Watch a folder and rsync files to a destination on change
watchnrun() {
    # shellcheck disable=SC3043
    local watchDir dir evt file fn _action action exclude i
    action="$*"
    watchDir="$1"
    shift

    if [ -z "$watchDir" ]; then
        echo >&2 "Usage: watchnrun <watchDir> <command>"
        return 2
    fi

    exclude=".git|node_modules|vendor|composer.json|composer.lock|composer-setup.php"

    while i=$(
        inotifywait -qr -e modify -e create \
            --exclude "$exclude" \
            "$watchDir"
    ); do
        set -- $i

        dir=$1
        evt=$2
        file=$3
        fn="$dir$file"

        echo
        echo "$evt $fn"

        case $evt in
        MODIFY | CREATE)
            case $file in
            *Test.php)
                set -- $action
                _action=
                while [ $# -ne 0 ]; do
                    if [ "$1" = "--filter" ]; then
                        shift
                    else
                        _action="$_action $1"
                    fi
                    shift
                done
                i=${file%.*}
                _action="$_action --filter ${i%.*}"
                ;;
            *)
                _action="$action"
                ;;
            esac

            eval "$_action"
            ;;
        *)
            echo >&2 "evt $evt ($i) not implemented yet"
            ;;
        esac
    done
}

install_dev() {
    version=${1:?}
    version_num=$(ver_num "$version")
    phpunit_ver=$(var "PHPUNIT_VERSION_$version_num")
    shift

    echo "PHP ver: $version"
    echo "PHPUnit ver: $phpunit_ver"

    if [ -z "$phpunit_ver" ]; then
        echo >&2 "No PHPUnit version associated with this version of PHP"
        return 2
    fi

    # php < 5.6
    if php -r "die(+version_compare(PHP_VERSION,'5.6','>='));"; then
        curl -ks -L https://curl.se/ca/cacert.pem >/etc/ssl/certs/ca-certificates.crt
    fi

    # shellcheck disable=SC3043
    local preinstall postinstall
    preinstall="preinstall_dev_$version_num"

    if type "$preinstall" >/dev/null 2>&1; then
        "$preinstall"
    fi

    # if ! command -v git >/dev/null || ! command -v unzip >/dev/null; then
    #     apk -U add git unzip
    #     # apt update && apt install -y git unzip
    # fi

    composer="$workdir/vendor/bin/composer$version_num"

    if [ ! -s "$composer" ]; then
        # Install composer
        [ -d "$(dirname "$composer")" ] || mkdir -p "$(dirname "$composer")"
        ( cd "$HOME" && \
            php -r 'copy("https://getcomposer.org/installer", "composer-setup.php");' &&
            php composer-setup.php --install-dir="$(dirname "$composer")" --filename="$(basename "$composer")" &&
            php -r 'unlink("composer-setup.php");' \
        )
    fi
    export PATH="$(realpath "$workdir/vendor/bin"):$PATH"

    # Running the tests for a specific PHP version should not change composer.json
    [ -s composer.json.lock ] ||
        cp -f -- composer.json composer.json.lock

    trap "cd '$workdir' && [ -s composer.json.lock ] && mv -f -- composer.json.lock composer.json" \
        INT TERM EXIT

    "$composer" require --dev "phpunit/phpunit:$phpunit_ver" -W
    # "$composer" dump-autoload

    # install composer dependencies
    if php -r "die(+(version_compare(PHP_VERSION,'5.5')!=1));"; then
        "$composer" install $flags
    else
        "$composer" dump-autoload
    fi

    postinstall="postinstall_dev_$version_num"
    if type "$postinstall" >/dev/null 2>&1; then
        "$postinstall"
    fi

    # The section bellow serves as an example for future, when there are dependencies:

    # # Update some dependencies to this PHP version
    # "$composer" require --dev symfony/css-selector symfony/dom-crawler

    # # Some dependencies are available for PHP >= 5.5 only
    # if php -r "die(+version_compare(PHP_VERSION,'5.5','<'));"; then
    #     "$composer" require --dev php-http/mock-client php-http/discovery guzzlehttp/psr7 php-http/message php-http/message-factory
    # fi

    # # Remove some tools not required for testing
    # "$composer" remove --dev apigen/apigen
}

postinstall_dev_83() {
    echo "Installing PHP CS Fixer for PHP 8.3..."
    $composer require --dev friendsofphp/php-cs-fixer $flags
}

# preinstall_dev_54() {
#     if ! command -v unzip >/dev/null; then
#         apt update && apt install -y unzip
#     fi
# }

# preinstall_dev_53() {
#     if ! command -v unzip >/dev/null; then
#         apt update && apt install -y unzip
#     fi
# }

# preinstall_dev_55() {
#     curl -ks https://curl.se/ca/cacert.pem >/etc/ssl/certs/ca-certificates.crt
# }

docker_run() {
    # shellcheck disable=SC3043
    local image vendorDir
    image="$1"
    vendorDir="$workdir/tmp/$image/vendor"
    [ -d "$vendorDir" ] || mkdir -p "$vendorDir"
    docker run --rm "-i$(tty -s && echo t)" \
        -v "$workdir:/app" \
        -u "$(id -u):0" \
        -e "HOME=/app/vendor" \
        --mount 'type=bind,"src='"$vendorDir"'",dst=/app/vendor' \
        -w /app "$@"
}

main() {
    # shellcheck disable=SC3043
    local version docker_tag

    # By default test the latest PHP version
    [ $# -eq 0 ] && set -- "$(composer_php_vers | tail -1)"

    case $1 in
    main_in_docker)
        shift
        main_in_docker "$@"
        return $?
        ;;

    edge)
        shift
        hi=$(composer_php_vers | head -1)
        lo=$(composer_php_vers | tail -1)
        echo Running tests for $hi and $lo supported PHP versions &&
            main "$hi" "$@" &&
            main "$lo" "$@" &&
        echo && echo "All done"
        return $?
        ;;

    all)
        shift
        echo Running tests for all supported PHP versions &&
            main 8.4 "$@" &&
            main 8.3 "$@" &&
            main 8.2 "$@" &&
            main 8.1 "$@" &&
            main 8.0 "$@" &&
            main 7.4 "$@" &&
            main 7.3 "$@" &&
            main 7.2 "$@" &&
            main 7.1 "$@" &&
            echo && echo "All done"
        return $?
        ;;

    v) composer_php_vers; return $? ;;

    h | help | -h | --help | \?)
        usage
        return 0
        ;;

    esac

    version=$1
    docker_tag="$version-$(var "DOCKER_VERSION_$(ver_num "$version")" 'alpine')"

    case $2 in
    bash | sh)
        shift
        docker_run "php:$docker_tag" "$@"
        return $?
        ;;
    esac

    rm -f -- "$workdir/composer.lock"

    if [ -f /.dockerenv ]; then
        main_in_docker "$@"
    else
        docker_run "php:$docker_tag" sh "/app/$myname" "main_in_docker" "$@"
    fi
}

main "$@"
