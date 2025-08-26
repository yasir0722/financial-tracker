#!/bin/bash

echo "Setting up Laravel Financial Tracker Application..."

# Create Laravel project if it doesn't exist
if [ ! -f "composer.json" ]; then
    echo "Installing Laravel..."
    composer create-project --prefer-dist laravel/laravel . "10.*"
fi

# Create environment file
if [ ! -f ".env" ]; then
    echo "Creating environment file..."
    cp .env.example .env
fi

# Install dependencies
echo "Installing Composer dependencies..."
composer install

echo "Installing Node.js dependencies..."
npm install

# Generate application key
echo "Generating application key..."
php artisan key:generate

# Set proper permissions
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo "Laravel setup complete!"
echo "Run 'docker-compose up -d' to start the application"
