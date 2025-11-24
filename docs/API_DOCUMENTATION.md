# CRM API Documentation

This documentation provides a detailed reference for the CRM API. The API is built with Symfony 7.2 and supports a multi-tenant architecture.

## Base URL
`http://localhost:8000` (Development)

## Authentication & Headers

The API requires specific headers for authentication and tenant context.

| Header          | Value              | Description                                    |
| --------------- | ------------------ | ---------------------------------------------- |
| `Authorization` | `Bearer <token>`   | JWT Token for user authentication              |
| `X-API-KEY`     | `<apiKey>`         | API Key for service-to-service authentication  |
| `X-Tenant`      | `<tenant_name>`    | The identifier of the tenant (e.g., `default`) |
| `Content-Type`  | `application/json` | Required for POST/PUT/PATCH requests           |

## Response Format

All API responses follow a consistent JSON structure:

```json
{
    "status": "success" | "error",
    "data": { ... }, // The requested data or null
    "message": "Optional message", // Error message or success confirmation
    "page": 1, // (Optional) Current page for paginated lists
    "limit": 25, // (Optional) Items per page
    "total": 100 // (Optional) Total items count
}
```

## Endpoints

### Contacts (`/contact`)

| Method   | Endpoint                               | Description                                           |
| -------- | -------------------------------------- | ----------------------------------------------------- |
| `GET`    | `/contact/search/{contains}`           | Search contacts by name, email, etc.                  |
| `POST`   | `/contact/list`                        | List contacts with pagination and filters.            |
| `GET`    | `/contact/info/{id}`                   | Get detailed contact information.                     |
| `POST`   | `/contact/edit`                        | Create (id=null) or update a contact.                 |
| `POST`   | `/contact/import`                      | Import contacts from CSV/Excel (Multipart form-data). |
| `GET`    | `/contact/associate/{id}/{idCompany}`  | Associate a contact with a company.                   |
| `GET`    | `/contact/unassociate/{id}`            | Disassociate a contact from a company.                |
| `GET`    | `/contact/merge/{sourceId}/{targetId}` | Merge source contact into target contact.             |
| `POST`   | `/contact/addPhoto`                    | Add/Update contact photo (Base64).                    |
| `DELETE` | `/contact/delete/{id}`                 | Soft delete a contact.                                |

**Example `POST /contact/edit` Body:**
```json
{
    "id": null,
    "source": "website",
    "manager": "commercial@example.com",
    "company": {"id": 1},
    "properties": [
        {"propertyModel": {"id": 1}, "value": "John Doe"},
        {"propertyModel": {"id": 2}, "value": "john@example.com"}
    ],
    "tags": [{"id": 1}]
}
```

### Companies (`/company`)

| Method   | Endpoint                               | Description                               |
| -------- | -------------------------------------- | ----------------------------------------- |
| `POST`   | `/company/list`                        | List companies with pagination.           |
| `GET`    | `/company/search/{contains}`           | Search companies by name.                 |
| `GET`    | `/company/info/{id}`                   | Get detailed company information.         |
| `POST`   | `/company/edit`                        | Create or update a company.               |
| `POST`   | `/company/import`                      | Import companies from file.               |
| `POST`   | `/company/addPhoto`                    | Add/Update company logo.                  |
| `GET`    | `/company/merge/{sourceId}/{targetId}` | Merge source company into target company. |
| `DELETE` | `/company/delete/{id}`                 | Soft delete a company.                    |

### Deals (`/deal`)

| Method   | Endpoint                                        | Description                         |
| -------- | ----------------------------------------------- | ----------------------------------- |
| `POST`   | `/deal/edit`                                    | Create or update a deal.            |
| `GET`    | `/deal/info/{id}`                               | Get deal details.                   |
| `PATCH`  | `/deal/change/step/{id}`                        | Change the pipeline step of a deal. |
| `GET`    | `/deal/win/{id}`                                | Mark deal as won.                   |
| `GET`    | `/deal/lose/{id}`                               | Mark deal as lost.                  |
| `GET`    | `/deal/unlose/unwin/{id}`                       | Reset deal status.                  |
| `GET`    | `/deal/dissociate/contact/{id}`                 | Remove main contact association.    |
| `GET`    | `/deal/dissociate/company/{id}`                 | Remove company association.         |
| `GET`    | `/deal/remove/participant/{dealId}/{contactId}` | Remove a participant.               |
| `DELETE` | `/deal/delete/{id}`                             | Soft delete a deal.                 |

**Example `POST /deal/edit` Body:**
```json
{
    "id": null,
    "object": "Deal Name",
    "manager": "user@example.com",
    "contact": {"id": 1},
    "company": {"id": 1},
    "step": {"id": 1},
    "products": ["PROD_A"],
    "participants": [{"id": 2}],
    "tags": [{"id": 1}]
}
```

### Activities (`/activity`)

| Method   | Endpoint                | Description                                       |
| -------- | ----------------------- | ------------------------------------------------- |
| `GET`    | `/activity/list`        | List activities.                                  |
| `POST`   | `/activity/calendar`    | Get calendar view (requires date range in query). |
| `GET`    | `/activity/{id}`        | Get activity details.                             |
| `POST`   | `/activity/edit`        | Create or update an activity.                     |
| `DELETE` | `/activity/delete/{id}` | Delete an activity.                               |

**Example `POST /activity/edit` Body:**
```json
{
    "id": null,
    "name": "Call",
    "type": "call",
    "startDate": "2024-01-15T14:00:00Z",
    "endDate": "2024-01-15T14:30:00Z",
    "location": "Office",
    "performed": false,
    "notify": true,
    "description": "Discussion",
    "deal": {"id": 1},
    "contact": {"id": 1}
}
```

### Pipelines (`/pipeline`)

| Method   | Endpoint                | Description                      |
| -------- | ----------------------- | -------------------------------- |
| `GET`    | `/pipeline/list`        | List all pipelines.              |
| `GET`    | `/pipeline/info/{id}`   | Get pipeline details with steps. |
| `POST`   | `/pipeline/edit`        | Create or update a pipeline.     |
| `DELETE` | `/pipeline/delete/{id}` | Delete a pipeline.               |

### Pipeline Steps (`/pipeline/step`)

| Method   | Endpoint                           | Description                         |
| -------- | ---------------------------------- | ----------------------------------- |
| `GET`    | `/pipeline/step/list`              | List all steps.                     |
| `GET`    | `/pipeline/step/list/{pipelineId}` | List steps for a specific pipeline. |
| `POST`   | `/pipeline/step/edit`              | Create or update a step.            |
| `DELETE` | `/pipeline/step/delete/{id}`       | Delete a step.                      |

### Tags (`/tag`)

| Method | Endpoint      | Description                |
| ------ | ------------- | -------------------------- |
| `GET`  | `/tag/list`   | List all tags.             |
| `POST` | `/tag/create` | Create a new tag.          |
| `POST` | `/tag/assign` | Assign a tag to an entity. |

### Notes (`/note`)

| Method   | Endpoint            | Description              |
| -------- | ------------------- | ------------------------ |
| `POST`   | `/note/edit`        | Create or update a note. |
| `DELETE` | `/note/delete/{id}` | Delete a note.           |

### Emails (`/mail`) & Phone Numbers (`/phone/number`)

| Method   | Endpoint                    | Description                 |
| -------- | --------------------------- | --------------------------- |
| `GET`    | `/mail/`                    | List emails.                |
| `POST`   | `/mail/edit`                | Create/Update email.        |
| `DELETE` | `/mail/delete/{id}`         | Delete email.               |
| `GET`    | `/phone/number/`            | List phone numbers.         |
| `GET`    | `/phone/number/{id}`        | Get phone number details.   |
| `POST`   | `/phone/number/edit`        | Create/Update phone number. |
| `DELETE` | `/phone/number/delete/{id}` | Delete phone number.        |

### Properties (`/property`) & Models (`/property/model`)

| Method   | Endpoint                      | Description                         |
| -------- | ----------------------------- | ----------------------------------- |
| `GET`    | `/property/list`              | List properties.                    |
| `POST`   | `/property/edit`              | Create/Update property.             |
| `DELETE` | `/property/delete/{id}`       | Delete property.                    |
| `GET`    | `/property/model/list`        | List property models (definitions). |
| `POST`   | `/property/model/edit`        | Create/Update property model.       |
| `DELETE` | `/property/model/delete/{id}` | Delete property model.              |

### Item Types (`/item/type`) & Files (`/file`)

| Method   | Endpoint                 | Description                  |
| -------- | ------------------------ | ---------------------------- |
| `GET`    | `/item/type/list`        | List item types.             |
| `GET`    | `/item/type/{code}`      | Get item type by code.       |
| `POST`   | `/item/type/edit`        | Create/Update item type.     |
| `DELETE` | `/item/type/delete/{id}` | Delete item type.            |
| `GET`    | `/file/list`             | List files.                  |
| `POST`   | `/file/edit`             | Create/Update file metadata. |
| `DELETE` | `/file/delete/{id}`      | Delete file.                 |

### Admin (`/admin`) & Export

| Method | Endpoint                         | Description             |
| ------ | -------------------------------- | ----------------------- |
| `GET`  | `/admin/get/routes`              | Get all API routes.     |
| `POST` | `/admin/save/route/requirements` | Save route permissions. |
| `GET`  | `/export-full`                   | Export all data as ZIP. |
