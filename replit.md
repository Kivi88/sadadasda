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
- July 08, 2025. **Major Feature Update**: 
  - Added max amount limits to keys (prevents users from ordering more than allowed)
  - Implemented real API order submission to external providers (MedyaBayim)
  - Added order ID copy functionality throughout the system
  - Created success modal with auto-redirect to order search after order creation
  - Enhanced service search with both name and external ID support
  - All orders now properly sync with external APIs and track real order status
- July 08, 2025. **Migration & Enhancement Update**:
  - Successfully migrated project from Replit Agent to Replit environment
  - Fixed order creation API integration (service ID now properly passed to backend)
  - Enhanced user experience with loading animations and visual feedback
  - Added auto-redirect functionality after successful order creation
  - Improved error handling and logging for API requests
  - Database setup completed with PostgreSQL integration
- July 08, 2025. **Order System Enhancement**:
  - Fixed order creation to properly include service ID in API requests
  - Enhanced API integration with better error handling and logging
  - Added automatic redirect to order search page after successful order creation
  - Implemented 5-second auto-redirect timer with visual countdown
  - Improved order-to-API data flow for proper external order processing
- July 08, 2025. **Key Validation & Single-Use System**:
  - Enhanced key validation UI to show amount range based on key's max amount (0 - max_amount)
  - Added proper link and quantity input fields in key validation component
  - Implemented single-use key functionality - keys deactivate after reaching max amount
  - Fixed real API order submission to external providers (MedyaBayim API)
  - Improved order tracking with proper external order ID integration
  - Added visual feedback showing remaining key amount after validation
- July 08, 2025. **Universal API Integration & Flexible Service Fetching**:
  - Implemented universal API service fetching system supporting any API provider
  - Added configurable limit controls for service import (0 = unlimited, custom amounts)
  - Enhanced API format detection for multiple response structures (services/data/result)
  - Improved service ID mapping from various field names (service, id, serviceId, service_id)
  - Added test mode for quick API validation (100 services)
  - Implemented detailed progress reporting with processed/skipped counts
  - Fixed database connection issues by switching from WebSocket to standard PostgreSQL
- July 08, 2025. **Migration & Homepage Update**:
  - Successfully migrated project from Replit Agent to Replit environment
  - Set up PostgreSQL database with proper environment variables
  - Created new homepage with simplified key validation interface
  - Updated branding to "KIWIPAZARI" with Turkish language support
  - Implemented clean, dark-themed UI matching user specifications
- July 08, 2025. **Key Validation & Single-Use System**:
  - Enhanced key validation UI to show amount range based on key's max amount (0 - max_amount)
  - Added proper link and quantity input fields in key validation component
  - Implemented single-use key functionality - keys deactivate after reaching max amount
  - Fixed real API order submission to external providers (MedyaBayim API)
  - Improved order tracking with proper external order ID integration
  - Added visual feedback showing remaining key amount after validation
- July 08, 2025. **Project Migration & Homepage Redesign**:
  - Successfully migrated project from Replit Agent to Replit environment
  - Set up PostgreSQL database with proper environment variables
  - Created custom homepage with KiWiPazan branding and Turkish language support
  - Implemented simple key validation interface matching user's design requirements
  - Reorganized routing structure with dedicated homepage at root path

## User Preferences

Preferred communication style: Simple, everyday language.