# FUT Activity

 is a module to track activity in multiple content entities, it also provides a way to sort tracked entities by their activity value.

## TODO:
this module is far from being finished and polished these are the next steps

- discuss default plugins to add (comments, groups)
- tests
- improve logic of running decay plugins (differentiate what is a normal activity plugin from decay plugin)


## Installation

### (work in progress)
this module hopefully will end up as contrib module right now it lives  in custom/modules.

to enable it just use the ui or:
```bash
drush en fut_activity
```

## Usage
- ### Create a tracker:
```
- go to /admin/config/entity_activity_tracker/add
- select which entity type / bundle it will track
- configure plugins to apply
- make sure that the plugin "Entiy Create" is enabled (THIS IS IMPORTANT)
- optionally you can also configure a decay plugin.
(more plugin in the future)
```
- ### Add sort in a view:
```
- go to a view edit page (listing tracked entities)
- on SORT CRITERIA add "Fut Activity: Activity"
```


## Contributing
Pull requests are welcome. <br>
For major changes, please open an issue first to discuss what you would like to change.


## License
IDK?

[MIT](https://choosealicense.com/licenses/mit/)

## Questions, proposals, etc...
fell free to [mail](mailto:adrianodias1994@gmail.com) me

