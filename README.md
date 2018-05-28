Main info requested
=============================

Installation steps
-------------------
1) install and configure mysql
2) install and configure webserver (should point into /web directory)
3) run `composer install`
4) run `composer update`
5) `php bin/console doctrine:schema:update --force`
6) run `./vendor/phpunit`, check if no errors on tests

You are free to go with APP :)

Message
-------------------
Hello! Thank you for such detailed task description.

First of all, i should say that i had an experience with Symfony 2, like a 2 years ago. 
So i checked what changed in new versions of Symfony, but i had like an hour on this (so if you see some old methods used, you know why) :)

Answering your question, what i did about performance optimization:
1) connected APP to Redis; 
2) moved sessions to Redis;
3) enabled doctrine to use Redis as cache;
4) in a most huge request - posts list (api/posts) i enabled object caching using Redis.

Also, for performance increase i configured mysql server to use query cache (SET GLOBAL query_cache_type = 1;)

For adding comments i decided to use bundle, that provides all requested functionality (FOSUserBundle).

Main controller, that contains all methods to work with posts (and API) - `src/Controller/PostController.php`.
Also, you may check commits log, to have an idea about process of task implementation.

Thank you for your time and i hope that my solution will fit your needs!


What could be improved?
-------------------
Well, a lot of things. Most important ones (IMHO):
1) Put email outboung into queue
2) Open API for public and add OAuth for this purpose
3) Create a profile page, also provide a possibility to create OAuth API key here
4) Add media content to posts.
5) Add a powerfull post input (like tinyMCE)
6) Show a comments count on each posts in a posts list
7) Style all of this

Test Task: Posts and Comments
=============================

We're providing a basic structure for you, so you can focus more on what actually matters. Here is what we're asking for:
  * If user is not authenticated, redirect to login (restrict access if not authenticated)
  * Add option to sign up
  * If logged in, redirect to `/posts`. If just registered, redirect also to `/posts` but add a hint on successful registration
    * (optional) require confirmation of email address to get access to posts
  * on `/posts`:
    * Add methods to create post (optionally over REST API and dynamically adding into the feed)
    * Add method to add comments via REST API (required) and dynamically add to post
    * Add method to remove own posts or comments
  
  * Add a `Comment` entity to `Posts`
  * Add author information to `Posts`
  * Optimize for performance and explain which steps you took
  * Write tests to check functionality
  * Add instructions on how to setup project, run tests and what could be improved in further steps
  * (optional) add timezone support and serve content according to users timezone
  * Provide results within own git repository and add your remarks within this README or as comments within code (but describe here where to find those comments)
  
What's already done
-------------------

  * Basic symfony (3.4) structure
  * `User` and `Post` entity
  * Included frontend libraries:
    * jQuery (1.12.4)
    * Bootstrap (3.3.7)
  * Included bundles:
    * `FOSRestBundle`
    * `FOSUserBundle`
    * `JMSSerializerBundle`