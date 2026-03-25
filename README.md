# ECM-Track: Care Management Module

A web-based care management system designed for California's **Enhanced Care Management (ECM)** program under **CalAIM** (California Advancing and Innovating Medi-Cal). It helps community-based healthcare organizations coordinate care for high-risk Medi-Cal members with complex medical, behavioral health, and social needs.

## Tech Stack

- **Laravel 12** — PHP backend framework
- **Livewire 3** — Full-stack reactive components
- **Alpine.js** — Lightweight JavaScript framework (bundled with Livewire)
- **Tailwind CSS 4** — Utility-first CSS framework
- **Jetstream** — Authentication scaffolding (login, registration, profile management)
- **SQLite** — Database (development)

## Core Features

### Care Management Module (PTR Hierarchy)
The system follows a strict **Problem → Task → Resource** workflow:

```
Member (Patient)
  └─ Problem (health/social issue)         [ADD → CONFIRM → RESOLVE]
      └─ Task (action to address problem)  [ADD → APPROVE? → START → COMPLETE]
          └─ Resource (review/assessment)   [ADD — immutable]
```

- **State Machine Workflow** — Not simple CRUD; enforces strict state transitions with audit trails
- **Problem Categories** — Physical, Behavioral, SUD, SDOH (Housing, Food, Transportation, Other)
- **Role-Based Access Control** — Every action is role-dependent
- **Concurrency Control** — Problem-level locking, one editor at a time
- **Append-Only Notes** — Compliance with medical records practices
- **Full Audit Trail** — All state changes logged with user identity and timestamps

### Member Management
- Member listing with search and filtering
- Member profile with demographics (Name, DOB, Member ID, Organization)
- Status tracking (Active/Inactive)

### Authentication & Security
- Login/Registration via Laravel Jetstream
- Profile management and password updates
- Two-factor authentication support
- Dark/Light mode with cookie persistence

### UI Features
- Collapsible left sidebar navigation
- Responsive layout
- Dark/Light mode toggle
- Care Management page with category filtering sidebar

## Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ & npm

### Installation

```bash
# Clone the repository
git clone https://github.com/gaarakcal/eCM-Track.git
cd eCM-Track

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed the database
php artisan migrate --seed

# Build frontend assets
npm run build

# Start the development server
php artisan serve
```

The application will be available at `http://localhost:8000`.

### Default Login
After seeding, use the following credentials:
- **Email:** test@example.com
- **Password:** password

## Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage
```

The project includes **165+ tests** covering:
- Unit tests for models, enums, and user actions
- Feature tests for authentication flows
- Feature tests for Livewire components (Care Management CRUD, state transitions, modals)
- State machine workflow tests

## Project Structure

```
app/
├── Enums/              # ProblemState, TaskState, ProblemType, TaskType, etc.
├── Exceptions/         # InvalidStateTransition, StaleModel
├── Livewire/
│   └── CareManagement/ # Livewire components (Index, ProblemDetail, TaskDetail, Modals)
├── Models/             # Member, Problem, Task, Resource, Note, StateChangeHistory
├── Policies/           # Authorization policies
├── Services/
│   └── CareManagement/ # StateMachineService, PtrValidationService
└── View/Components/    # Layout components

tests/
├── Unit/               # Model tests, enum tests, user action tests
└── Feature/            # Auth tests, Livewire component tests, state machine tests
```

## Compliance

Built to support:
- **HIPAA** Privacy Rule and Security Rule
- **42 CFR Part 2** for substance use disorder information
- **CalAIM ECM** regulatory requirements

## License

This project is proprietary software. All rights reserved.
