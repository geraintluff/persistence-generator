# persistence-generator

A highly experimental and probably useless PHP/MySQL data storage framework, useful for creating HTML/JSON APIs.

## What?

You specify the shape of your data objects (objects properties, array items, etc.) and the framework then generates some PHP base classes for you, that deal with all the

You can have a look at the files called `test-genX.php` for some examples of me just messing around with the framework as I'm building it.  Have a look at the folder `cms/` to see me starting to build an API.

## Why?

Because you're not the boss of me.

## I just cloned it and it won't connect to MySQL!

Surprisingly enough, I didn't put my MySQL credentials up on GitHub for everyone to use.  There's a missing file `config.php`, which defines some constants, including the `MYSQL_*` constants used for the connection.
