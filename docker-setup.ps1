#!/usr/bin/env pwsh

Write-Host "Setting up Laravel Financial Tracker with Docker..." -ForegroundColor Green

# Check if Docker is available
try {
    docker --version | Out-Null
    docker-compose --version | Out-Null
    Write-Host "Docker and Docker Compose found!" -ForegroundColor Green
} catch {
    Write-Host "Docker or Docker Compose not found. Please install Docker Desktop for Windows." -ForegroundColor Red
    Write-Host "Download from: https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
    exit 1
}

# Create a temporary Laravel installer container
Write-Host "Creating Laravel project using Docker..." -ForegroundColor Yellow

# Use a temporary composer container to create the Laravel project
docker run --rm -v "${PWD}:/app" -w /app composer:latest create-project --prefer-dist laravel/laravel . "10.*"

if ($LASTEXITCODE -ne 0) {
    Write-Host "Failed to create Laravel project with Docker." -ForegroundColor Red
    exit 1
}

# Create environment file
Write-Host "Setting up environment file..." -ForegroundColor Yellow
if (Test-Path ".env.docker") {
    Copy-Item ".env.docker" ".env"
} elseif (Test-Path ".env.example") {
    Copy-Item ".env.example" ".env"
    
    # Update database configuration for Docker
    $envContent = Get-Content ".env" -Raw
    $envContent = $envContent -replace "DB_HOST=127\.0\.0\.1", "DB_HOST=db"
    $envContent = $envContent -replace "DB_DATABASE=laravel", "DB_DATABASE=financial_tracker"
    $envContent = $envContent -replace "DB_USERNAME=root", "DB_USERNAME=financial_user"
    $envContent = $envContent -replace "DB_PASSWORD=", "DB_PASSWORD=financial_password"
    $envContent = $envContent -replace "CACHE_DRIVER=file", "CACHE_DRIVER=redis"
    $envContent = $envContent -replace "SESSION_DRIVER=file", "SESSION_DRIVER=redis"
    $envContent = $envContent -replace "REDIS_HOST=127\.0\.0\.1", "REDIS_HOST=redis"
    Set-Content ".env" $envContent
}

# Build and start containers
Write-Host "Building Docker containers..." -ForegroundColor Yellow
docker-compose build

Write-Host "Starting Docker containers..." -ForegroundColor Yellow
docker-compose up -d

# Wait for containers to be ready
Write-Host "Waiting for containers to start..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Install dependencies inside container
Write-Host "Installing dependencies..." -ForegroundColor Yellow
docker-compose exec -T app composer install --no-dev --optimize-autoloader

# Generate application key
Write-Host "Generating application key..." -ForegroundColor Yellow
docker-compose exec -T app php artisan key:generate

# Set permissions
Write-Host "Setting up permissions..." -ForegroundColor Yellow
docker-compose exec -T app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Run migrations
Write-Host "Running database migrations..." -ForegroundColor Yellow
docker-compose exec -T app php artisan migrate --force

Write-Host ""
Write-Host "🎉 Laravel Financial Tracker setup complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Your application is now running at:" -ForegroundColor Cyan
Write-Host "  🌐 Web: http://localhost:8000" -ForegroundColor White
Write-Host "  🗄️  Database: localhost:3306" -ForegroundColor White
Write-Host "  📦 Redis: localhost:6379" -ForegroundColor White
Write-Host ""
Write-Host "Useful commands:" -ForegroundColor Cyan
Write-Host "  docker-compose logs -f          # View logs" -ForegroundColor White
Write-Host "  docker-compose exec app bash    # Access app container" -ForegroundColor White
Write-Host "  docker-compose down             # Stop containers" -ForegroundColor White
