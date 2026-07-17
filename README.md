1. So put the helper file in the GLOBAL app/Helpers (if needed make one)
2. put this code in your composer.json file:
    "autoload": {
        "files": [
            "app/Helpers/form_helper.php"
        ]
    },

after that run composer dump-autoload

3. for using it you need to call it in your blade files like this:
{!! required_star('column that needs to be checked', 'table name') !!}

NOTICE, this helper only gets the ->required() from your migration 
