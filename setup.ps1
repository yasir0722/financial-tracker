#!/usr/bin/env pwsh

Write-Host "Setting up Laravel Financial Tracker Application..." -ForegroundColor Green

# Check if Laravel project exists
if (-not (Test-Path "composer.json")) {
    Write-Host "Installing Laravel..." -ForegroundColor Yellow
    composer create-project --prefer-dist laravel/laravel . "10.*"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to create Laravel project. Make sure Composer is installed." -ForegroundColor Red
        exit 1
    }
}

# Create environment file
if (-not (Test-Path ".env")) {
    Write-Host "Creating environment file..." -ForegroundColor Yellow
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
    } else {
        Copy-Item ".env.docker" ".env"
    }
}

# Install dependencies
Write-Host "Installing Composer dependencies..." -ForegroundColor Yellow
composer install

# Check if Node.js is available and install npm dependencies
try {
    node --version | Out-Null
    Write-Host "Installing Node.js dependencies..." -ForegroundColor Yellow
    npm install
} catch {
    Write-Host "Node.js not found. Skipping npm install. Please install Node.js to compile assets." -ForegroundColor Yellow
}

# Generate application key
Write-Host "Generating application key..." -ForegroundColor Yellow
php artisan key:generate

Write-Host "Laravel setup complete!" -ForegroundColor Green
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Run 'docker-compose up -d' to start the application" -ForegroundColor White
Write-Host "2. Run 'docker-compose exec app php artisan migrate' to set up the database" -ForegroundColor White
Write-Host "3. Access your application at http://localhost:8000" -ForegroundColor White
