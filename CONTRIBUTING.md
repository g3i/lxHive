# How to Contribute

lxHive is developed by its community consisting of lxHive users, enthusiasts,
Brightcookie employees, customers, partners, and others. We strongly encourage you to
contribute to lxHive's open source efforts by implementing new features,
enhancing existing features, and fixing bugs. We also welcome your participation
in writing documentation and guides.

To maintain code quality, all code changes are reviewed by a core set of lxHive project
maintainers, namely @sraka1 and @RoboSparrow.

As you have ideas for features you want to implement, follow the contribution
steps outlined in the sections, below. For more details on specific steps, and
to get a deeper understanding of lxHive in general, make sure to check out our Wiki.
Lastly, visit the links listed in the *Additional Resources* section, below.

## Getting Started

* Sign up for a [GitHub account](https://github.com/signup/free) (if you don't have one already) to be able to participate.
* [Create a new issue](https://github.com/Brightcookie/lxHive/issues/new) for your issue. If a ticket
already exists for the issue, participate via the existing ticket.
  * Describe the issue clearly. If it is a bug, include steps to reproduce it.
  * Select an appropriate category for the issue.
  * Select the earliest version (or commit) of the product affected by the issue.
* Fork the lxHive repository.

## Making Changes

* Ensure you have a working lxHive development environment. A quick startup guide on setting up an instance
is part of the README file included in this repository. 
* Create a branch from an existing branch (typically the *development* branch) from
which you want to base your changes.
Use the following nomenclature when naming your branch:
  * If you are working on a bugfix, prefix the branch name with bugfix-*
  * If you are working on a new feature/improvement, prefix the branch name with feature-*
  * If you are working solely on increasing ADL test compliance, prefix the branch name with compliance-*
* Commit logical units of work.
* Follow the [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md), 
[PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md), 
[PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and 
[Symfony](http://symfony.com/doc/current/contributing/code/standards.html) code standards.
* Include a reference to the issue (e.g. #13) in your commit messages.
For example:

        Improve code readability - #13

* *Test* your changes thoroughly! Consider the wide variety of operating
systems, databases, application servers, and other related technologies. Make sure your changes in one environment don't break something in
another environment. See the [xAPITests](https://github.com/sraka1/xAPITests) and [xAPI_LRS_Test](https://github.com/adlnet/xAPI_LRS_Test)
repositories for details on executing automated tests against your test instance.

## Submitting Changes

* Push changes in your branch to your fork.
* [Create a pull request](https://github.com/Brightcookie/lxHive/compare) and mention the issue it fixes [as described here](https://github.com/blog/1506-closing-issues-via-pull-requests).
* You're done! Well, not quite ... be sure to respond to comments and questions
to your pull request until it is closed.

## Additional Resources

* [lxHive Website](http://www.lxhive.com/)
* [General GitHub documentation](http://help.github.com/)
* [GitHub pull request
documentation](http://help.github.com/send-pull-requests/)
