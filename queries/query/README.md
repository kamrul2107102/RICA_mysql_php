# SQL Query Extraction - Summary

## Overview
All embedded SQL queries have been extracted from PHP API and page files into dedicated, consolidated SQL query files in the `queries/query/` directory. Each entity has its own single query file with named sections.

## Query Files Created

### 1. `queries/query/conferenceQuery.sql`
Contains all conference-related queries:
- COUNT_TOTAL, COUNT_LOCATIONS, COUNT_PAPERS
- GET_ONE, LIST_WITH_FILTERS, COUNT_WITH_FILTERS
- CHECK_EXISTS_BY_NAME_YEAR, CHECK_EXISTS_BY_ID
- INSERT, UPDATE_DYNAMIC, DELETE_BY_ID
- COUNT_PAPERS_BY_ID

### 2. `queries/query/institutionQuery.sql`
Contains all institution-related queries:
- GET_ONE, COUNT_TOTAL, COUNT_COUNTRIES
- COUNT_WITH_FILTERS, LIST_WITH_FILTERS
- CHECK_EXISTS_BY_NAME, CHECK_EXISTS_BY_ID
- INSERT, UPDATE, DELETE_BY_ID
- UPDATE_AUTHORS_SET_NULL (cascade)

### 3. `queries/query/journalQuery.sql`
Contains all journal-related queries:
- GET_ONE, GET_BY_ID, COUNT_TOTAL
- COUNT_PAPERS_BY_ID, LIST_WITH_PAPER_COUNT
- CHECK_EXISTS_BY_NAME, CHECK_HAS_PAPERS
- INSERT, UPDATE_DYNAMIC, DELETE_BY_ID

### 4. `queries/query/authorQuery.sql`
Contains all author-related queries:
- GET_ONE_WITH_INSTITUTION, COUNT_TOTAL
- COUNT_WITH_FILTERS, LIST_WITH_FILTERS
- CHECK_EMAIL_EXISTS, CHECK_EMAIL_EXISTS_EXCLUDE
- CHECK_INSTITUTION_EXISTS, CHECK_EXISTS_BY_ID
- CHECK_HAS_PAPERS
- INSERT, UPDATE_DYNAMIC, DELETE_BY_ID

### 5. `queries/query/paperQuery.sql`
Contains all paper-related queries:
- GET_ONE_WITH_DETAILS, GET_AUTHORS_FOR_PAPER
- COUNT_TOTAL, COUNT_WITH_FILTERS
- LIST_WITH_FILTERS
- INSERT, UPDATE_DYNAMIC, DELETE_BY_ID

### 6. `queries/query/citationQuery.sql`
Contains all citation-related queries:
- GET_ONE_WITH_DETAILS, COUNT_TOTAL
- GET_FOR_PAPER_CITING, GET_FOR_PAPER_CITED, GET_FOR_PAPER_BOTH
- LIST_WITH_DETAILS
- CHECK_PAPER_EXISTS, CHECK_CITATION_EXISTS, CHECK_EXISTS_BY_ID
- INSERT, UPDATE_DYNAMIC, DELETE_BY_ID

### 7. `queries/query/adminQuery.sql`
Contains all admin/system queries:
- GET_TABLE_MAX_ID, RESET_AUTO_INCREMENT
- COUNT_TABLE_ROWS, GET_DATABASE_SIZE
- SHOW_MYSQL_UPTIME, SHOW_ALL_TABLES
- GET_TABLE_STRUCTURE

### 8. `queries/query/analyticsQuery.sql`
Contains all analytics queries:
- COUNT_TOTAL_* (papers, authors, citations, journals, institutions, conferences)
- GET_TOP_CITED_PAPERS, GET_TOP_AUTHORS
- GET_PUBLICATION_STATS_BY_YEAR
- GET_INSTITUTION_STATS

### 9. `queries/query/exportQuery.sql`
Contains all export queries:
- GET_ALL_FROM_TABLE, GET_TABLE_FIELDS
- SHOW_ALL_TABLES, GET_TABLE_WITH_LIMIT
- GET_AUTHORS_FULL, GET_PAPERS_FULL, GET_CITATIONS_FULL

### 10. `queries/query/authQuery.sql`
Contains all authentication queries (for future expansion):
- GET_USER_BY_USERNAME, UPDATE_LAST_LOGIN
- CREATE_USER, CHECK_USERNAME_EXISTS, CHECK_EMAIL_EXISTS
- GET_ALL_USERS, DELETE_USER, UPDATE_USER_ROLE
- COUNT_ADMINS

## Refactored Files

### API Files
All API files now use the `sql_named()` and `sql_named_with()` helper functions:
- ✅ `api/conferences.php` - uses conferenceQuery.sql
- ✅ `api/institutions.php` - uses institutionQuery.sql
- ✅ `api/journals.php` - uses journalQuery.sql
- ✅ `api/authors.php` - uses authorQuery.sql
- ✅ `api/papers.php` - uses paperQuery.sql
- ✅ `api/citations.php` - uses citationQuery.sql
- ✅ `api/admin.php` - uses adminQuery.sql
- ✅ `api/analytics.php` - uses analyticsQuery.sql
- ✅ `api/export.php` - uses exportQuery.sql
- ✅ `api/auth.php` - uses authQuery.sql (queries for future expansion)

### Page Files
Updated to include sql.php and use query loader:
- ✅ `conferences.php` - statistics use conferenceQuery.sql
- ✅ `institutions.php` - statistics use institutionQuery.sql
- ✅ `authors.php` - dropdowns use institutionQuery.sql
- ✅ `papers.php` - dropdowns use journalQuery.sql, conferenceQuery.sql

## Query Loader Pattern

### Basic Named Query
```php
$sql = sql_named('conferenceQuery.sql', 'GET_ONE');
$stmt = $conn->prepare($sql);
```

### Named Query with Token Replacement
```php
$whereClause = "WHERE year = 2024";
$sql = sql_named_with('conferenceQuery.sql', 'LIST_WITH_FILTERS', ['WHERE' => $whereClause]);
$stmt = $conn->prepare($sql);
```

### In Query Files
```sql
-- name: GET_ONE
SELECT * FROM Conferences WHERE conference_id = ?

-- name: LIST_WITH_FILTERS
SELECT * FROM Conferences /*WHERE*/ ORDER BY name LIMIT ? OFFSET ?
```

The `/*WHERE*/` marker gets replaced by actual WHERE clauses at runtime.

## Benefits

1. **Maintainability**: All SQL for an entity is in one place
2. **Reusability**: Named queries can be shared across endpoints
3. **Testability**: SQL can be tested independently
4. **Security**: SQL is still prepared and parameterized
5. **Version Control**: SQL changes are easier to track
6. **Documentation**: Query files serve as SQL documentation

## Migration Notes

- All prepared statements and parameter binding remain unchanged
- The query loader is a thin wrapper that reads SQL files
- Token replacement (/*WHERE*/, /*SET*/) allows dynamic WHERE/SET clauses
- No runtime performance impact (file reads could be cached if needed)

## Verification

All files compiled successfully with no PHP errors. The SQL files show linter warnings because the linter expects T-SQL syntax, but all queries are valid MySQL/MariaDB syntax with prepared statement placeholders (`?`).

## Next Steps (Optional)

1. Add query result caching if needed
2. Create query unit tests
3. Add query performance monitoring
4. Document query naming conventions
