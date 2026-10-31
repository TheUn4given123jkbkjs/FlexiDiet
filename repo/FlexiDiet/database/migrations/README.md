# eNews interactions migration

For an existing database, run this once from Command Prompt (CMD), in the source
project directory:

```cmd
mysql -u root -p flexidiet < database\migrations\enews_interactions.sql
mysql -u root -p flexidiet < database\migrations\enews_public_profiles.sql
mysql -u root -p flexidiet < database\migrations\friendships.sql
```

Fresh installations should use `database/schema.sql` instead. Do not rerun
`schema.sql` against a database with user data; it drops and recreates tables.

The eNews migrations add public/private post visibility, threaded comments,
shares, deduplicated notifications, and public profile bios. The friendships
migration adds friend requests, accepted friendships, and friend activity
notifications. It should be applied after the eNews interactions migration.
Friend-only post visibility remains unavailable; friendship does not change
existing post privacy settings. New post images are stored outside `public`;
legacy eNews images are moved there on the first authenticated eNews request
and served only through the authorized media API.

The public profile migration stores only an optional eNews bio; health data from
`user_profiles` is not exposed in social profiles.
