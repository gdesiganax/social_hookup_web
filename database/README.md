# HookUp Database

Import these files in order:

```sql
SOURCE database/schema.sql;
SOURCE database/seed.sql;
```

For an existing database created before the gender/preference update, run:

```sql
SOURCE database/migration_2026_10_12_profiles_gender.sql;
```

For an existing database created before the wallet/gift update, run:

```sql
SOURCE database/migration_2026_10_12_wallet_gifts.sql;
```

For an existing database created before profile media, profile privacy, and naira gift pricing, run:

```sql
SOURCE database/migration_2026_10_12_profile_media_naira.sql;
```

For Paystack wallet funding and uploaded post/profile media, run:

```sql
SOURCE database/migration_2026_10_12_paystack_uploads.sql;
```

For phone, address, and Nigeria NIN account verification, run:

```sql
SOURCE database/migration_2026_10_12_user_verifications.sql;
```

For online profile indicators, run:

```sql
SOURCE database/migration_2026_10_13_user_presence.sql;
```

Paystack wallet funding needs these environment values or matching entries in `config/config.php`:

```text
PAYSTACK_PUBLIC_KEY=pk_test_or_live_key
PAYSTACK_SECRET_KEY=sk_test_or_live_key
PAYSTACK_TRANSACTION_CHARGE_PERCENT=3
```

Set the Paystack webhook URL to:

```text
https://your-domain.example/actions/paystack_webhook
```

Default local accounts use the password `password123`:

- `admin@hookup.local`
- `alex@example.com`

For production, create a dedicated MySQL user with only the privileges this database needs, update `config/config.php`, and replace all seeded passwords.
