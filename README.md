# View Modes Display

This module is used to preview view modes for your entities.

When working on a site with many content types and view modes 
for example, 
it becomes tedious to find out where to view the rendered entity 
in a given view mode. 
This helper module aims to solve this by providing an 
additional tab "View Mode Preview" on the entity itself.

On the main "View Mode Preview" page a list of all enabled view modes 
will be displayed as links to preview the current entity in the desired view mode
using the current enabled theme.

Alternatively all view modes can be previewed on one page by visiting:
`/{entity_type}/{$entity_id}/preview/all`

### Configuration

No configuration is needed,
enable the module and check for the "View Mode Preview" tab
on any entity.

### Supported Entities

All current core entities as of 8.6.x are supported, 
support for custom/contrib entities will be considered via the issue queue and patches are welcome!

### Credits

Development was sponsored by Marzee Labs
http://marzeelabs.org
