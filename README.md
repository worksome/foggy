# Foggy - Database scrubbing

Foggy is a package which creates a database dump of your database, but with scrubbed data.
Perfect for taking a production database and use locally.

## Install

For installation via Composer
````shell
composer require worksome/foggy
````

For installation in a Laravel application
```shell
composer require worksome/foggy-laravel
```

## Usage

For usage with Laravel, read more in [Laravel Foggy docs](https://github.com/worksome/foggy-laravel#usage).

## Configuration

The configuration lies in a JSON file, which has to adhere to the `schema.json` file.
You can validate your configuration file on [jsonschemavalidator.net](https://www.jsonschemavalidator.net/) or directly in phpstorm.

For the most basic configuration, where all tables are dumped, but no data is scrubbed, we simply do:

```json
{
  "database": {
    "*": {
      "withData": true
    }
  }
}
```

Here we have specified that all tables and views (`*`) should be dumped with data by default.
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
          "__comment": "Generate a fake name for all users, except if they are our own employees",
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

Each table can have an array of rules. Rules are applied to a specific column and can modify
the values in that column per row.

#### Faker

The faker rule is to replace the value with a new fake value.
It uses the [faker library](https://github.com/fzaninotto/Faker) underneath, so all formatters
available in faker can be used here.

For calling a simple faker property, simply specify `value` as the property you want to use.
In the following example we are calling the `email` faker.

```json
{
  "column": "some_column",
  "type": "faker",
  "value": "email"
}
```

Sometimes you might want to use faker formatters which takes arguments. Arguments can be
supplied by using the `params` key in the json object.
In the following example we specify that we only want to generate `male` names.

```json
{
  "column": "some_column",
  "type": "faker",
  "value": "firstName",
  "params": "male"
}
```

#### Replacer

The replacer rule replaces a column with the given value.
It's a simple rule for when you just want all entries to have the same value. A great use-case is for
setting all passwords to the same value, so when using the scrubbed database, you can log in on all user's
with the same password.
In the following example we replace all passwords with `secret`, but a hashed edition of it.

```json
{
  "column": "password",
  "type": "replace",
  "value": "$2y$10$xmVOYC1DUte0oG86Zz8oeeKc3UXZNrdSKMoZGrCElup6VexStFh22"
}
```

#### PHP

The PHP rule is a basic, but really powerful rule. It allows you to define a PHP string which will be applied
to the column.
This string has a few variables which can be accessed.
- `value` - this variable will hold the current value of the column which the rule should be applied to.
- `row` - This variable will hold the current values of the whole row which the rule should be applied to.

The PHP string will be evaluated, and the value returned from it will be the new value of the column. It is
not needed to write `return`, as the statement is wrapped in a `return` automatically.

```json
{
  "column": "ip",
  "type": "php",
  "value": "sha1($value)"
}
```

### Conditions

#### Times

It is possible to limit a column to only be applied `x` amount of times, by supplying an argument named
`times`. This will limit, so the rule is only applied until the `times` are hit.

### SQL Views

All views definitions live inside `database` key in the json object.
In opposition to tables, views do not have any particular rules applicable to them.

Only requirement is for them to be listed in the json object to be included in the import.
The wildcard configuration `*` will include them all.

```json
{
  "database": {
      "stock_quantity_view": {}
  }
}
```
