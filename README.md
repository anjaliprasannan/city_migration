# JSON Migration for City Entity

This project provides functionality to migrate JSON data into a custom `city` entity.

---

## Installation Steps

### 1. Install Required Modules

Enable the following custom modules:

- `custom_entity`
- `migration_json`

Use the command:

```bash
drush en custom_entity migration_json -y
```

### 2. Run the Migration

1. Navigate to the Migration configuration page:
   ```
   Admin -> Configuration -> JSON Migration
   ```
   Alternatively, visit directly:
   ```
   /admin/config/json_migration
   ```
2. Run the migration to import JSON data into the `city` entity.
   ```
   Alternatively, run command: drush migrate:import city_entity
   ```

### 3. View Imported Data

Once the migration is completed, you can view the imported `city` entities at:

```
Admin -> Content -> City
```

Alternatively, visit:

```
/admin/content/city
```

Edit the entries to view the fields.
