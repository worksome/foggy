# Foggy - Database scrubbing
Foggy is a package which creates a database dump of your database, but with scrubbed data.  
Perfect for taking a production database and use locally.

## Installation
For installation via composer
````bash
$ composer require worksome/foggy
````

For installation in a Laravel application (NOT YET CREATED)
```bash
$ composer require worksome/foggy-laravel
```

## Usage


## Configuration
The configuration lies in a JSON file, which has to adhere to the `schema.json` file.  
You can validate your configuration file on [jsonschemavalidator.net](https://www.jsonschemavalidator.net/) or directly in phpstorm.  

For the most basic configuration, where all tables are dumped, but no data is scrubbed, we simply do
```json
{
  "database": {
    "*": {
      "withData": true
    }
  }
}
```
Here we have specified that all tables (`*`) should be dumped with data by default.  
A more secure default would be to set `withData` to `false`, so only schema definitions are exported, if nothing specific is specified.

### Defining rules for a table
All table definitions live inside `database` key in the json object.  
Each table can have an array of rules. A rule consist of the following
- `column` - Which column to apply the rule to.
- `type` - Which rule type to use.
- `value` - The value supplied to the rule type.
- `times` (optional) - The amount of times this rule are allowed to be applied.
- `params` (optional) - Static parameters supplied to the rule.
- `condition` (optional) - Add a condition which has to pass before the rule is applied.

In the following snippet we have added some rules for the `users` table.
It shows a quick example of some rules and parameters.
```json
{
  "database": {
    "users": {
      "withData": true,
      "rules": [
        {
          "__comment": "Generate a fake name for all users, except if they are our own employees"
          "column": "name",
          "type": "faker",
          "value": "name",
          "condition": "!str_contains($row['email'], '@worksome.com')"
        },
        {
          "__comment": "Replace all avatars with a fake image based on their user id.",
          "column": "avatar",
          "type": "php",
          "value": "\"https://api.adorable.io/avatars/285/{$row['id']}.png\""
        },
        {
          "__comment": "Replace all passwords with `secret`",
          "column": "password",
          "type": "replace",
          "value": "$2y$10$xmVOYC1DUte0oG86Zz8oeeKc3UXZNrdSKMoZGrCElup6VexStFh22"
        }
      ]
    }
  }
}
```

### Rules

#### Faker

#### Replacer

#### PHP

### Conditions