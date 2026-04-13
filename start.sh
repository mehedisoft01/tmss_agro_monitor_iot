#!/bin/bash

PORT=${2:-8000}
SESSION="laravel-dev"
PHP_VERSION="8.4"
NODE_VERSION="22"
process=${1:-'start'}


if ! command -v php$PHP_VERSION >/dev/null 2>&1; then
    echo "php$PHP_VERSION not found. Installing PHP $PHP_VERSION..."

    sudo apt update
    sudo apt install -y software-properties-common
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt update

    sudo apt install -y \
        php$PHP_VERSION php$PHP_VERSION-cli php$PHP_VERSION-common php$PHP_VERSION-curl \
        php$PHP_VERSION-mbstring php$PHP_VERSION-xml php$PHP_VERSION-zip php$PHP_VERSION-mysql \
        php$PHP_VERSION-bcmath php$PHP_VERSION-intl

    echo "✅ PHP $PHP_VERSION installed successfully"
fi

export NVM_DIR="$HOME/.nvm"
if ! command -v nvm >/dev/null 2>&1; then
    echo "NVM not found. Installing NVM..."
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.6/install.sh | bash
fi

if ! command -v tmux >/dev/null 2>&1; then
    echo "tmux not found. Installing on Ubuntu..."
    sudo apt update
    sudo apt install -y tmux
    echo "tmux installed successfully"
fi

if ! nvm ls $NODE_VERSION >/dev/null 2>&1; then
    echo "Node $NODE_VERSION not found. Installing..."
    nvm install $NODE_VERSION
fi

[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"

if [[ "$process" != "job" ]]; then
    echo "Using Node version $NODE_VERSION..."
    nvm use $NODE_VERSION

    echo "Killing any process using TCP port $PORT..."
    fuser -n tcp -k $PORT
    tmux kill-session -t $SESSION

    tmux new-session -d -s $SESSION

    tmux send-keys -t $SESSION "php$PHP_VERSION artisan serve --port=$PORT" C-m
    tmux split-window -h -t $SESSION
    tmux send-keys -t $SESSION "npm run watch" C-m

    tmux attach -t $SESSION
else
    php artisan queue:restart
    php artisan queue:retry all
    php artisan queue:work --timeout=0
fi
