# replit.md

## Overview

This is a full-stack web application built with React, TypeScript, and Express.js that provides an API marketplace management system. The application allows administrators to manage external APIs, services, keys, and orders while providing a client interface for key validation and order management.

## System Architecture

### Frontend Architecture
- **Framework**: React with TypeScript
- **Styling**: Tailwind CSS with shadcn/ui components
- **State Management**: TanStack Query for server state
- **Routing**: Wouter for client-side routing
- **Build Tool**: Vite for development and build processes

### Backend Architecture
- **Runtime**: Node.js with Express.js
- **Database**: PostgreSQL with Drizzle ORM
- **Database Provider**: Neon Database (serverless PostgreSQL)
- **API Architecture**: RESTful API with JSON responses
- **Session Management**: Express sessions with PostgreSQL storage

### Project Structure
```
├── client/                 # React frontend
│   ├── src/
│   │   ├── components/     # UI components
│   │   ├── hooks/         # Custom React hooks
│   │   ├── lib/           # Utility functions
│   │   └── pages/         # Page components
├── server/                # Express backend
│   ├── db.ts             # Database connection
│   ├── routes.ts         # API routes
│   ├── storage.ts        # Data access layer
│   └── vite.ts           # Vite integration
├── shared/               # Shared types and schemas
└── migrations/           # Database migrations
```

## Key Components

### Database Schema
- **APIs**: External API providers with authentication keys
- **Services**: Individual services offered by APIs (followers, likes, etc.)
- **Keys**: Generated access keys for clients
- **Orders**: Client orders for services

### Authentication & Authorization
- Session-based authentication using express-session
- PostgreSQL session store via connect-pg-simple
- No complex user management - simplified admin/client model

### API Integration
- Abstracted storage layer supporting multiple external APIs
- Service synchronization from external providers
- Order processing and status tracking

## Data Flow

1. **Admin Flow**: 
   - Add/manage external API connections
   - Sync services from external APIs
   - Generate and manage client keys
   - Monitor orders and system status

2. **Client Flow**:
   - Validate access keys
   - Place orders for services
   - Track order status

3. **System Flow**:
   - External API integration for service data
   - Order processing and fulfillment
   - Real-time status updates

## External Dependencies

### Core Dependencies
- **@neondatabase/serverless**: Serverless PostgreSQL client
- **drizzle-orm**: Type-safe ORM for PostgreSQL
- **@tanstack/react-query**: Server state management
- **@radix-ui/***: Headless UI components
- **tailwindcss**: Utility-first CSS framework

### Development Dependencies
- **vite**: Build tool and dev server
- **typescript**: Type checking
- **@replit/vite-plugin-***: Replit-specific tooling

## Deployment Strategy

### Development
- Vite dev server for frontend with HMR
- Express server with middleware integration
- Database migrations via Drizzle Kit
- Environment-based configuration

### Production
- Vite build process generates optimized static assets
- Express serves both API and static files
- Database provisioning via Replit/Neon integration
- Environment variable configuration for DATABASE_URL

### Build Process
```bash
npm run build    # Build both frontend and backend
npm run start    # Start production server
npm run dev      # Start development server
```

## Changelog
- July 08, 2025. Initial setup
- July 08, 2025. Fixed API service fetching to properly connect to external APIs and fetch all available services (removed mock fallback)
- July 08, 2025. Fixed API key field mapping issue and removed invalid price field - now successfully imports all 4,802+ services from MedyaBayim API
- July 08, 2025. Fixed API service fetching to use v1 endpoints instead of v2, improved error handling and added multiple endpoint testing strategies

## User Preferences

Preferred communication style: Simple, everyday language.