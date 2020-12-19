# Development helpers

Testing has been difficult in early times of this project, so here we
collect scripts for testing various parts of the code, especially
localizations for $YOUR_LAB.

## Visitor leave listing

Usage: `./trigger_last_leave [-c NOTIFIER.CONF] -v VISITOR_JSON`

If you omit `-c`, then default configuration in project root is
used. The syntax for configuration is described in the
[example](../../notifier.conf.example).

Example: `./trigger_last_leave -v visitors_test1.json`

Help wanted! Construct more JSON files for testing some corner cases.
