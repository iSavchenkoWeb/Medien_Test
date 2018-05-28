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
