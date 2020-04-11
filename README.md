# Convert http rest client to CURL

If you are using phpstorm or other jetbrain editor you might need to export your http rest client to curl format. So this litle script can help you with that.

## How to use?

First copy your http rest client to clipboard then run this script.

```php
php to-curl.php
```

With input like this :
```bash
DELETE http://localhost:8000/api/v1/users
Authorization: Bearer 123
x-api-permanent-delete: true
Content-Type: application/json

{
  "data": [
    {
      "type": "member",
      "id": 3
    },
    {
      "type": "member",
      "id": 4
    }
  ]
}
```

Will export to :

```bash
curl -X DELETE http://localhost:8000/api/v1/users \
-H 'Authorization: Bearer 123' \
-H 'x-api-permanent-delete: true' \
-H 'Content-Type: application/json' \
-d '{
  "data": [
    {
      "type": "member",
      "id": 3
    },
    {
      "type": "member",
      "id": 4
    }
  ]
}'
```

## Contribute
Since this script only works for linux, you can make PR to support mac or window.

## Licence
MIT.
