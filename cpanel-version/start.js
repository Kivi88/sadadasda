#!/usr/bin/env node

/**
 * KiWiPazari Startup Script for cPanel
 * Bu script cPanel ortamında uygulamayı başlatır
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

console.log('🚀 KiWiPazari starting up...');

// Check if .env file exists
if (!fs.existsSync('.env')) {
    console.log('⚠️  .env file not found. Please copy .env.example to .env and configure it.');
    console.log('💡 Example configuration:');
    console.log('   DATABASE_URL=postgresql://user:pass@localhost:5432/dbname');
    console.log('   PORT=3000');
    console.log('   NODE_ENV=production');
    process.exit(1);
}

// Load environment variables
require('dotenv').config();

// Check required environment variables
const requiredEnvVars = ['DATABASE_URL'];
const missingEnvVars = requiredEnvVars.filter(envVar => !process.env[envVar]);

if (missingEnvVars.length > 0) {
    console.log('❌ Missing required environment variables:');
    missingEnvVars.forEach(envVar => {
        console.log(`   - ${envVar}`);
    });
    console.log('💡 Please check your .env file configuration.');
    process.exit(1);
}

// Set default port if not specified
const PORT = process.env.PORT || 3000;
console.log(`📡 Starting server on port ${PORT}`);

// Start the main application
const app = spawn('node', ['index.js'], {
    stdio: 'inherit',
    env: { ...process.env, PORT }
});

app.on('close', (code) => {
    console.log(`🔴 Application exited with code ${code}`);
    process.exit(code);
});

app.on('error', (err) => {
    console.error('❌ Failed to start application:', err);
    process.exit(1);
});

// Handle graceful shutdown
process.on('SIGINT', () => {
    console.log('🛑 Shutting down gracefully...');
    app.kill('SIGINT');
});

process.on('SIGTERM', () => {
    console.log('🛑 Shutting down gracefully...');
    app.kill('SIGTERM');
});