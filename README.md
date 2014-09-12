GitHubLatch
===========

GitHub provide Webhooks, which are incredibly useful for those wanting to automate development and release. However, the documentation is incredibly lacking, and the only PHP tool is seriously over-complicated for what I needed.

Thus, GitHubLatch was born!

Well, it's caveman-esque ancestor was, anyway.

Originally a procedural script that only knew how to handle straight pushes, running `fetch` and `merge` if the payload matched the current branch and just a `fetch` on all other directories.

Reinvisioned in my spare time to be an object capable of running highly-specified commands! Yay!

## Installation ##

TBA

## Usage ##

TBA

## To Do ##

* Allow non-Git commands
* Support more events!

## License ##

As usual with my work, this project is available under the BSD 3-Clause license. In short, you can do whatever you want with this code as long as:

* I am always recognised as the original author.
* I am not used to advertise any derivative works without prior permission.
* You include a copy of said license and attribution with any and all redistributions of this code, including derivative works.

For more details, read the included LICENSE.md file or read about it on [opensource.org](http://opensource.org/licenses/BSD-3-Clause).