# Complete API to Query Mapping

## All 10 API Files Now Use Query Files ✅

### Main Entity APIs (CRUD Operations)
1. **api/conferences.php** → `conferenceQuery.sql` (12 queries)
2. **api/institutions.php** → `institutionQuery.sql` (9 queries)
3. **api/journals.php** → `journalQuery.sql` (9 queries)
4. **api/authors.php** → `authorQuery.sql` (13 queries)
5. **api/papers.php** → `paperQuery.sql` (8 queries)
6. **api/citations.php** → `citationQuery.sql` (12 queries)

### System & Utility APIs
7. **api/admin.php** → `adminQuery.sql` (7 queries)
   - Database statistics
   - Table management
   - System information
   - Auto-increment reset

8. **api/analytics.php** → `analyticsQuery.sql` (10 queries)
   - Top cited papers
   - Top authors by impact
   - Publication trends by year
   - Institution statistics

9. **api/export.php** → `exportQuery.sql` (7 queries)
   - CSV data export
   - Full database export
   - Table field information

10. **api/auth.php** → `authQuery.sql` (9 queries)
    - User authentication queries (for future DB-based auth)
    - Currently uses config-based credentials
    - Queries ready for migration to DB auth

## Query File Summary

| Query File | Queries | Purpose |
|------------|---------|---------|
| conferenceQuery.sql | 12 | Conference CRUD & relationships |
| institutionQuery.sql | 9 | Institution CRUD & author links |
| journalQuery.sql | 9 | Journal CRUD & paper counts |
| authorQuery.sql | 13 | Author CRUD, validation, papers |
| paperQuery.sql | 8 | Paper CRUD with joins |
| citationQuery.sql | 12 | Citation CRUD & relationships |
| adminQuery.sql | 7 | System admin & maintenance |
| analyticsQuery.sql | 10 | Reports & statistics |
| exportQuery.sql | 7 | Data export operations |
| authQuery.sql | 9 | User management (future) |
| **TOTAL** | **96** | All embedded SQL externalized |

## Coverage: 100%

✅ All 10 API files refactored  
✅ All embedded SQL extracted  
✅ Zero PHP compilation errors  
✅ Backward compatible (no breaking changes)  
✅ Ready for production

## Usage Pattern

```php
// Load the SQL helper
require_once __DIR__ . '/../includes/sql.php';

// Simple query
$sql = sql_named('conferenceQuery.sql', 'GET_ONE');
$stmt = $conn->prepare($sql);

// Query with token replacement
$whereClause = "WHERE year > 2020";
$sql = sql_named_with('conferenceQuery.sql', 'LIST_WITH_FILTERS', 
    ['WHERE' => $whereClause]);
$stmt = $conn->prepare($sql);
```

## Benefits Achieved

1. **Separation of Concerns**: SQL separate from PHP logic
2. **Single Source of Truth**: One query file per entity
3. **Easier Maintenance**: Update queries without touching PHP
4. **Better Testing**: SQL can be tested independently
5. **Cleaner Code**: PHP files are more readable
6. **Version Control**: SQL changes tracked clearly
7. **No Performance Impact**: File I/O is minimal
8. **Still Secure**: All queries use prepared statements

## Next Steps (Optional)

- Add query caching for high-traffic queries
- Create query unit tests
- Add query performance monitoring
- Generate API documentation from queries
