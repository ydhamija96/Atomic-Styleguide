# Atomic-Styleguide

This documentation is incomplete (obviously). If necessary, I'll update it.
Also, this project was not deemed high priority. It was mostly done for fun. I would not be surprised if there are bugs.

# Explanation:

This is an automated styleguide generator for atomic web design. It takes a BitBucket repo containing individual components and templates, and
displays a nice website with each of its compoenents and templates in a nice organized fashion. It provides the ability to review designs,
copy/paste the individual code for components, as well download only required files (among other things). 

It has no special requirements other than that of a standard PHP server,
not even a database. All that must be done is to set it up on a PHP server (can be done locally, you don't have to make a website out of it),
set index.php to point to the right BitBucket repo, and you're golden.

# Example:

In most projects, design code resides in a repository with a file structure resembling something like this:

main.css

assets/

templates/

components/

scripts.js

If you want to display a component, you have to hunt it down in the file structure, fix its relative links by hand,
then open it and look at it, and then change the links back when you're done.

This program will do that for you, along with generating a style guide for developers to use when the design phase is complete.

<strong>Most important</strong> is that it generates a complete, automated, always-up-to-date styleguide for developers and designers
to use.

The importance of a styleguide, of course, is to ensure consistent style of components in a single page, among multiple pages in the same
website, and among multiple websites that are related.

As an example, a university has a bunch of different schools. All of those schools have their own websites. All of those websites share
a similar design. You bet the university has a styleguide to act as a guideline for these websites. This program will automatically generate
that styleguide from a pre-existing repository. And, when an update is made to the design, the styleguide will reflect that <bold>instantly</bold>.
