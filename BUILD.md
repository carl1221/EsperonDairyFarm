# Build Instructions

## Backend (PHP)
```bash
# Install dependencies
composer install

# Run tests
composer test

# Start development server
composer start
```

## Frontend (HTML/CSS/JS)
```bash
# Install build tools
npm install

# Build for production
npm run build

# Development with file watching
npm run watch

# Serve frontend locally
npm run serve
```

## Full Application Setup
```bash
# 1. Set up database
mysql -u root -p < esperon_dairyfarm.sql

# 2. Install backend dependencies
composer install

# 3. Install frontend build tools
npm install

# 4. Build frontend assets
npm run build

# 5. Start backend server
/c/xampp/php/php.exe -S localhost:8000 -t dairy_farm_backend/api

# 6. Open frontend in browser
# http://localhost:8000/dairy_farm_frontend/login.html
```

## Production Deployment
1. Run `npm run build` to minify assets
2. Update HTML files to reference minified assets:
   - `css/style.min.css` instead of `css/style.css`
   - `dist/js/app.min.js` instead of individual JS files
3. Deploy to web server with PHP support

## File Structure
```
esperon/
├── dairy_farm_backend/     # PHP API
├── dairy_farm_frontend/    # Static frontend
├── dist/                   # Built assets (after npm run build)
├── tests/                  # PHPUnit tests
├── api-docs.json          # OpenAPI documentation
├── composer.json          # PHP dependencies
├── frontend-package.json  # Frontend build config
└── phpunit.xml           # Test configuration