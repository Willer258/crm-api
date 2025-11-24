# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Symfony 7.2 CRM application** with multi-tenant architecture, built with:
- **PHP 8.2+** with strict typing and modern features
- **Doctrine ORM 3.3+** for database management
- **MySQL** with database-per-tenant architecture
- **JWT Authentication** via `lexik/jwt-authentication-bundle`
- **RESTful API** with attribute-based routing
- **TypeScript/JavaScript** integration for React and Vue.js frontends

## Development Commands

### Testing
- Run all tests: `php bin/phpunit`
- Run specific test: `php bin/phpunit tests/Controller/ContactControllerTest.php`
- Run tests with coverage: `php bin/phpunit --coverage-html coverage`

### Database Operations
- Create migrations: `php bin/console doctrine:migrations:diff`
- Run migrations: `php bin/console doctrine:migrations:migrate`
- Load fixtures: `php bin/console doctrine:fixtures:load`
- Initialize fixtures: `php bin/console app:init-fixtures`

### Cache and Assets
- Clear cache: `php bin/console cache:clear`
- Install assets: `php bin/console assets:install`
- Install importmap: `php bin/console importmap:install`

### Custom Commands
- Debug application: `php bin/console app:debug`
- Generate JS classes: `php bin/console app:generate-js-class`
- Generate access map: `php bin/console app:generate-access-map`
- Tenant management:
  - Set tenant: `php bin/console app:tenant:set <tenant>`
  - Get current tenant: `php bin/console app:tenant:get`
  - Execute command for each tenant: `php bin/console app:tenant:foreach <command>`

### Composer
- Install dependencies: `composer install`
- Update dependencies: `composer update`
- Autoload optimization: `composer dump-autoload`

### Development Server
- Start server: `symfony server:start` or `php -S localhost:8000 -t public`
- Stop server: `symfony server:stop`

## Architecture Overview

### Multi-Tenancy System
This CRM implements a sophisticated multi-tenant architecture:
- **Switcher** (`src/MultiTenancy/Switcher.php`): Handles tenant switching and database connection management
- **ConnectionWrapper** (`src/MultiTenancy/ConnectionWrapper.php`): Wraps Doctrine connections for tenant-specific databases
- **KernelListener** (`src/MultiTenancy/KernelListener.php`): Listens to kernel events to switch tenants automatically
- **Zone** (`src/MultiTenancy/Zone.php`): Manages tenant zones and configurations

Tenant switching is controlled by:
- Request headers or parameters
- Command-line tenant file (`tenant.txt`)
- Environment configuration

### Core CRM Entities
The system manages standard CRM entities with relationships:
- **Contact** (`src/Entity/Contact.php`): Customer contact information with company associations
- **Company** (`src/Entity/Company.php`): Business entities that can have multiple contacts
- **Deal** (`src/Entity/Deal.php`): Sales opportunities linked to contacts/companies
- **Activity** (`src/Entity/Activity.php`): Interactions and tasks (calls, meetings, emails)
- **Pipeline/PipelineStep** (`src/Entity/Pipeline.php`, `src/Entity/PipelineStep.php`): Sales process management
- **Note** (`src/Entity/Note.php`): Comments and observations
- **Mail/PhoneNumber** (`src/Entity/Mail.php`, `src/Entity/PhoneNumber.php`): Communication details
- **Tag** (`src/Entity/Tag.php`): Categorization system
- **Property/PropertyModel** (`src/Entity/Property.php`, `src/Entity/PropertyModel.php`): Custom field definitions
- **User** (`src/Entity/User.php`): User authentication and authorization
- **ItemType** (`src/Entity/ItemType.php`): Defines types of entities (contact, company, etc.)
- **Asset** (`src/Entity/Asset.php`): File attachments and documents
- **Device/Location** (`src/Entity/Device.php`, `src/Entity/Location.php`): Device tracking and location data

### Manager Pattern
Business logic is encapsulated in Manager classes (`src/Managers/`):
- Each entity has a corresponding Manager (e.g., `ContactManager`, `CompanyManager`)
- Managers handle complex operations, validations, and business rules
- Import managers handle data import from external sources
- All managers extend the base `Manager` class

### Security & Authentication
- **JWT Authentication**: Uses `lexik/jwt-authentication-bundle` for user authentication
  - Configure in `config/packages/lexik_jwt_authentication.yaml`
  - Generate keys: `php bin/console lexik:jwt:generate-keypair`
- **API Key Authentication**: Custom authenticator (`src/Security/ApiKeyAuthenticator.php`) for service-to-service communication
  - Pattern: `/service/*` routes
- **Master Authenticator**: Special authentication (`src/Security/MasterAuthenticator.php`) for cross-tenant operations
- **Access Decision Manager**: Custom logic (`src/Security/AccessDecisionManager.php`) for fine-grained permissions
- **Role Hierarchy**:
  - `ROLE_PRE_AUTH`: Pre-authenticated users
  - `ROLE_CUSTOMER`: Basic customer access (inherits ROLE_PRE_AUTH)
  - `ROLE_USER`: Standard user access
  - `ROLE_MANAGER`: Manager access (inherits ROLE_USER, ROLE_CUSTOMER)
  - `ROLE_ADMIN`: Admin access (inherits ROLE_MANAGER)
- **Access Control**: Currently set to `PUBLIC_ACCESS` for all routes (configure per environment)

### API Structure
Controllers follow RESTful patterns with attribute-based routing:
- All controllers extend `AbstractController`
- Route groups by entity (e.g., `/contact`, `/company`, `/deal`)
- Consistent JSON API responses with status/data structure
- Serialization groups for different API contexts (`list`, `info`, `edit`, `userManagement`)

**Available Controllers:**
- `ContactController`: Contact management (list, info, edit, import, merge, associate, delete)
- `CompanyController`: Company management (list, search, info, edit, import, merge, delete)
- `DealController`: Deal/opportunity management (edit, info, change step, win/lose, delete)
- `ActivityController`: Activity management (list, calendar, show, edit, delete)
- `PipelineController`: Pipeline management (list, info, edit, delete)
- `PipelineStepController`: Pipeline step management (list, edit, delete)
- `TagController`: Tag management (list, create, assign)
- `NoteController`: Note management (edit, delete)
- `MailController`: Email address management (list, edit, delete)
- `PhoneNumberController`: Phone number management (list, show, edit, delete)
- `PropertyController`: Property management (list, edit, delete)
- `PropertyModelController`: Property model management (list, edit, delete)
- `ItemTypeController`: Item type management (list, show, edit, delete)
- `FileController`: File/asset management (list, edit, delete)
- `AdminController`: Admin operations (get routes, save route requirements)
- `ExportController`: Data export (export-full)

### JavaScript Integration
- TypeScript classes for both React and Vue.js frontends in `jsClasses/`
- Auto-generation of JS classes from PHP entities
- Stimulus controllers for frontend interactivity
- Asset mapping for modern JavaScript bundling

### Database Configuration
- Uses MySQL with custom connection wrapper for multi-tenancy
- Doctrine ORM with attribute-based mapping
- UUID support via `ramsey/uuid-doctrine`
- Database per tenant architecture

## Development Guidelines

### Entity Development
- Use PHP 8.2+ features (attributes, typed properties)
- Follow UUID as primary identifier pattern where applicable
- Implement proper serialization groups for API responses
- Use traits for common functionality:
  - `UserObjectTrait`: Adds audit fields (createdAt, updatedAt, createdBy, updatedBy, createdFromIp, removeAt, etc.)
  - `TagTrait`: Adds tagging support
  - `ImageTrait`: Adds image/photo support

### Controller Development
- Use attribute routing with descriptive options (e.g., `#[Route('/contact/list', name: 'app_contact_list', options: ['description' => 'Liste tous les contacts'])]`)
- Implement consistent error handling and JSON responses
- Utilize dependency injection for managers and repositories
- Follow the existing pagination pattern for list endpoints:
  ```php
  $data = json_decode($request->getContent(), true);
  $results = $repository->listEntity($data);
  $total = $repository->getCount();
  return $this->json([
      'status' => 'success',
      'data' => $results,
      'page' => $data['pagination']['page'] ?? 1,
      'limit' => $data['pagination']['limit'] ?? 25,
      'total' => $total
  ], 200, [], ['groups' => 'entity:list']);
  ```
- Always specify serialization groups in JSON responses
- Use appropriate HTTP methods (GET, POST, PUT, PATCH, DELETE)

### Testing
- Test files are located in `tests/` directory
- Controller tests follow the pattern `{Entity}ControllerTest.php`
- Use Symfony's test framework with PHPUnit bridge
- Test database is automatically suffixed with `_test`

### Security Considerations
- Never commit sensitive data (API keys, passwords) to version control
- Use environment variables for all configuration
- Implement proper authorization checks in controllers
- Validate and sanitize all user inputs

### Multi-Tenant Development
- Always consider tenant context in business logic
- Use the Switcher class for programmatic tenant changes
- Test functionality across different tenant configurations
- Be aware of cross-tenant data isolation requirements

## Common Patterns

### Manager Usage
```php
public function __construct(
    private ContactManager $contactManager,
    private ContactRepository $contactRepository
) {}
```

### API Response Format
```php
return $this->json([
    'status' => 'success|error',
    'data' => $result,
    'message' => 'Optional message'
], 200, [], ['groups' => 'serialization:group']);
```

### Tenant Switching
```php
$this->switcher->switchTo('tenant_name');
// Perform tenant-specific operations
```

## API Endpoints Summary

### Contacts (`/contact`)
- `GET /contact/search/{contains}` - Search contacts by name/email
- `POST /contact/list` - List contacts with pagination and filters
- `GET /contact/info/{id}` - Get contact details
- `POST /contact/import` - Import contacts from file (CSV/Excel)
- `POST /contact/edit` - Create or update contact
- `GET /contact/associate/{id}/{idCompany}` - Associate contact with company
- `GET /contact/unassociate/{id}` - Disassociate contact from company
- `GET /contact/merge/{sourceId}/{targetId}` - Merge two contacts
- `POST /contact/addPhoto` - Add photo to contact
- `DELETE /contact/delete/{id}` - Delete contact

### Companies (`/company`)
- `POST /company/list` - List companies with pagination
- `GET /company/search/{contains}` - Search companies
- `GET /company/info/{id}` - Get company details
- `POST /company/import` - Import companies from file
- `POST /company/edit` - Create or update company
- `POST /company/addPhoto` - Add logo to company
- `GET /company/merge/{sourceId}/{targetId}` - Merge two companies
- `DELETE /company/delete/{id}` - Delete company

### Deals/Opportunities (`/deal`)
- `POST /deal/edit` - Create or update deal
- `GET /deal/info/{id}` - Get deal details
- `PATCH /deal/change/step/{id}` - Change deal pipeline step
- `GET /deal/win/{id}` - Mark deal as won
- `GET /deal/lose/{id}` - Mark deal as lost
- `GET /deal/unlose/unwin/{id}` - Reset deal status
- `GET /deal/dissociate/contact/{id}` - Dissociate main contact from deal
- `GET /deal/dissociate/company/{id}` - Dissociate company from deal
- `GET /deal/remove/participant/{dealId}/{contactId}` - Remove participant from deal
- `DELETE /deal/delete/{id}` - Soft delete deal

### Activities (`/activity`)
- `GET /activity/list` - List activities with filters
- `POST /activity/calendar` - Get calendar view of activities
- `GET /activity/{id}` - Get activity details
- `POST /activity/edit` - Create or update activity
- `DELETE /activity/delete/{id}` - Delete activity

### Pipelines (`/pipeline`)
- `GET /pipeline/list` - List pipelines
- `GET /pipeline/info/{id}` - Get pipeline with steps
- `POST /pipeline/edit` - Create or update pipeline
- `DELETE /pipeline/delete/{id}` - Delete pipeline

### Pipeline Steps (`/pipeline/step`)
- `GET /pipeline/step/list` - List all pipeline steps
- `GET /pipeline/step/list/{pipelineId}` - List steps for specific pipeline
- `POST /pipeline/step/edit` - Create or update pipeline step
- `DELETE /pipeline/step/delete/{id}` - Delete pipeline step

### Tags (`/tag`)
- `GET /tag/list` - List all tags
- `POST /tag/create` - Create new tag
- `POST /tag/assign` - Assign tag to entity

### Notes (`/note`)
- `POST /note/edit` - Create or update note
- `DELETE /note/delete/{id}` - Delete note

### Emails (`/mail`)
- `GET /mail/` - List email addresses
- `POST /mail/edit` - Create or update email
- `DELETE /mail/delete/{id}` - Delete email

### Phone Numbers (`/phone/number`)
- `GET /phone/number/` - List phone numbers
- `GET /phone/number/{id}` - Get phone number details
- `POST /phone/number/edit` - Create or update phone number
- `DELETE /phone/number/delete/{id}` - Delete phone number

### Properties (`/property`)
- `GET /property/list` - List properties
- `POST /property/edit` - Create or update property
- `DELETE /property/delete/{id}` - Delete property

### Property Models (`/property/model`)
- `GET /property/model/list` - List property models
- `POST /property/model/edit` - Create or update property model
- `DELETE /property/model/delete/{id}` - Delete property model

### Item Types (`/item/type`)
- `GET /item/type/list` - List item types
- `GET /item/type/{code}` - Get item type by code
- `POST /item/type/edit` - Create or update item type
- `DELETE /item/type/delete/{id}` - Delete item type

### Files (`/file`)
- `GET /file/list` - List files/assets
- `POST /file/edit` - Create or update file metadata
- `DELETE /file/delete/{id}` - Delete file

### Admin (`/admin`)
- `GET /admin/get/routes` - Get all API routes with permissions
- `POST /admin/save/route/requirements` - Save route permission requirements

### Export
- `GET /export-full` - Export all CRM data as ZIP (CSV files)

## Important Notes

### Serialization Groups
Use these groups for API responses:
- `contact:list`, `company:list`, `deal:list` - For list views
- `contact:info`, `company:info`, `deal:info` - For detail views
- `contact:edit`, `company:edit`, `deal:edit` - For edit/create operations
- `activity:read` - For activity responses
- `pipeline:list`, `pipeline:info` - For pipeline responses
- `userManagement` - For user-related data
- `infos` - For metadata and additional information

### Response Format
All API responses follow this structure:
{
    "status": "success|error",
    "data": {...},
    "message": "Optional message",
    "page": 1,       // For list endpoints
    "limit": 25,     // For list endpoints
    "total": 100     // For list endpoints
}
```

### Authentication Headers
```
Authorization: Bearer <JWT_TOKEN>
X-API-KEY: <API_KEY>
X-Tenant: <tenant_name>
```

### Multi-Tenant Considerations
- Each tenant has its own database
- Use `app:tenant:set <tenant>` to switch tenants in CLI
- Tenant switching happens automatically via headers in web requests
- All data is isolated per tenant

## Dependencies

### Key Symfony Bundles
- `symfony/framework-bundle`: Core framework
- `doctrine/orm`: Database ORM
- `lexik/jwt-authentication-bundle`: JWT authentication
- `nelmio/cors-bundle`: CORS support
- `symfony/serializer`: JSON serialization

### Additional Libraries
- `ramsey/uuid`: UUID generation
- `phpoffice/phpspreadsheet`: Excel/CSV import/export
- `matomo/device-detector`: Device detection

## Documentation References
- Full API documentation: See `docs/API_DOCUMENTATION.md`
- Postman collection: See `CRM_API.postman_collection.json`