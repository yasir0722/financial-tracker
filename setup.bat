@echo off
echo Setting up Laravel Financial Tracker Application...

REM Create Laravel project if it doesn't exist
if not exist "composer.json" (
    echo Installing Laravel...
    composer create-project --prefer-dist laravel/laravel . "10.*"
)

REM Create environment file
if not exist ".env" (
    echo Creating environment file...
    copy .env.example .env
)

REM Install dependencies
echo Installing Composer dependencies...
composer install

echo Installing Node.js dependencies...
npm install

REM Generate application key
echo Generating application key...
php artisan key:generate

echo Laravel setup complete!
echo Run 'docker-compose up -d' to start the application
