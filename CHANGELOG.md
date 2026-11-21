## Changelog

### Unreleased

- new (links): Link, a link of a site by its kind (plain, more, button, primary), its address built for the page shown (the engine's Url), its text escaped
- new (forms): a database the grid has a default for, its default kept or chosen (`fallback`, `useDefault`)
- new (lists): the texts of a list as the engine names them (`texts` of Contract::listOf(): title, menu, placeholder, empty, count)
- fix (forms): a condition on a box met by the box as a form sends it (checked, or left out)
- new (settings): the entry of a settings form in a menu and its message once saved (`menu`, `saved` of Contract::settingsOf())
- new (forms): the fields the options of the grid set, shown and never changed (FormView `shown`)
- new (forms): a database field as its parts, its password kept unless one is typed (`connection-string`)
- new (access): who may change the settings of the site, as the contract says it (`Contract::siteAccess()`, `x-site`), the engine's rule without it
- new (posts): the stream of an avatar for the avatar itself (PostsView, Contract::postsOf()): the fields that write a post as the engine describes them, the actions of its posts, the requests that do them; nobody else gets one
- new (items): the form of an item (FormView): the roles of the viewer, its groups with the fields it sees, the fields it changes with their value, the ones it may not, and what a posted form changes
- new (items): the view of an item (ItemView): its header (name and picture as its list shows them, round for a person; its state, the indicators of its row; its actions, the safe ones first) and its groups of fields without what the header shows
- new (lists): the view of a filter (FilterView): its field (one value, several, or a value written in its format), its values with their label key, text and help, the values the page asks; ListView gives its filters and counts the ones asked from them
- new: the library, its repository, its tests and its build
- new: the contract (Contract), who may do an operation (Access), the message once an action is done (Done), the dates (Dates)
- new: the view of a list (ListView): its columns, its rows (RowView) with their indicators (IndicatorView) and actions (ActionView), the rules of a row (Rows)
- new: a filter the bridge fixes to one value (the grid of the site) leaves its column and the filter out of the view
