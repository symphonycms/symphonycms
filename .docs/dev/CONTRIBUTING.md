# Contributing to Symphony

Symphony is an open source product under the MIT license, meaning that we are very much open to contributions from our users and the wider community
Before you start, there's a couple of things you might like to know.

- [Our Issue Tracker](y#our-issue-tracker)
- [Roadmap](#roadmap)
- [What server specifications should I be aiming for](#what-server-specifications-should-i-be-aiming-for)
- [Pull Requests](#pull-requests)
- [Code formatting](#code-formatting)
- [Test and build](#test-and-build)

## Our Issue Tracker

All Symphony's issues are tracked on our [issue tracker](https://github.com/symphonycms/symphonycms/issues).
For a better understanding of the tags used on the tracker, [check out our handy reference guide](https://github.com/symphonycms/symphonycms/wiki/Issue-Tracker-Tag-Reference).
All issues are open for contribution, but if there is already a user assigned it's probably best to check in with them first to see if they'd like a hand.

## Roadmap

Watch this space, Symphony does not currently have a publicly available roadmap. While there are ideas scattered throughout the issue tracker and others that are discussed offline, we have yet to formalise this into a roadmap. At the moment, we make use of Github milestones to add _short_ notes about releases but this is something we will rectify in future!

## What server specifications should I be aiming for?

At the time of writing, the latest version of Symphony should work on PHP5.6+.
Symphony 4.0.0 will drop support for PHP 5.6 and 7.0 and focus on PHP7.1+.
Symphony is built and tested on Apache and due to our limited resources, we don't wish to expand into other web servers. It is known to run under nginx also.

## Pull Requests

Great, you've made a fix or added something new? To make things easier for everyone ensure that your are submitting your pull request against the `version.number.x` branches.
The `master` branch is the last full official release (not Betas or RCs). Never submitted against `master`.
The `lts` branch contains the latest Long Term Support version, which may or may not differ from `master`.

1.	Create a new branch for each separate issue you want to work on, remembering to use a descriptive name

		git branch your-branch-name version.number.x

2.	When you are ready to be famous, rebase the `version.number.x` branch back into the branch you have worked on. This will ensure your work sits on top of the current `version.number.x` branch and is not intertwined between multiple commits. For a bonus you might like to squash your work into fewer commits!

		git rebase -i integration

3.	Publish your branch to your fork on Github and open up a Pull Request (remember `version.number.x` ;))

		git push your-fork your-branch-name

## Code formatting

### Commenting

We use PHPDoc and JSDoc to comment the Symphony core, so please comment all new functions and classes accordingly. We use a fork of PHPDoctor to [generate](https://github.com/symphonycms/symphonycms/wiki/Creating-API-Documentation) the [API Docs](https://www.getsymphony.com/learn/api/).
This is an example PHPDoc comment:

	/**
	 * Given a string (expected to be a URL parameter) this function will
	 * ensure it is safe to embed in an XML document.
	 *
	 * @since Symphony 2.3.1
	 * @param string $parameter
	 *  The string to sanitize for XML
	 * @return string
	 *  The sanitized string
	 */
	public static function sanitizeParameter($parameter) {
		return XMLElement::stripInvalidXMLCharacters(utf8_encode(urldecode($parameter)));
	}

If you are adding a new class or function, please add a `@since` attribute, (`@since Symphony 2.3.2`) so that we can accurately document the Symphony API per version.

When documenting `@param` or `@return` use the short variable type for consistency, `int` vs `integer`, `bool` vs `boolean` etc.
This is because the long form is an object, not a primitive.

### Deprecation policy

We aim to maintain extension compatibility between all minor versions (2.3.1, 2.3.2, 2.3.x, etc.) except in rare circumstances.
Any deprecations should be marked in the appropriate comment block, eg. `@deprecated This function will be removed in Symphony 2.4. Use PageManager::resolvePageFileLocation`.
Depending on the complexity of the code that has been refactored, deprecations usually occur for the next scheduled major release.

### Code style

We aim to be PSR-2 Compatible in the future and we use `phpcs` in order to validate our PSR-1 compliance. This means:

- 4 spaces soft tabs
- braces on a new line as the method or class definition
- braces on the same line as the condition
- `elseif` blocks on the same line
- always indent single if clauses
- return early
- `camelCase` method names
- multi-line arrays should use a trailing comma for the last array element

Do you know how to use [PHP Codesniffer](http://pear.php.net/package/PHP_CodeSniffer/)? Please help in fixing bugs!
You can run `grunt phpcs` to get a list of errors!

If anything is missing from here, please add it, or create an issue to elevate it to someone who can!

## Test and build

Symphony uses [npm](https://npmjs.com/) to run tests and build minified assets,
so make sure you have node and npm installed and in your path.
Every time you make a change in any css or javascript file, you must run the grunt tasks in order to test your code, since Symphony always load the minified versions.

1. Install all dependencies from the repository’s root:

		npm install

2. Build the assets

		npm run build

3. Run the test suite

		npm test

4. Optionally, you can minify script and style files upon saving source files

		npm run watch

5. Optionally, if you run a local install, you can run the integration tests

		npm run integration
