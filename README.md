# ppma2-demo
Demo for PPMA Multi-Let rent management software (discontinued)

IMPORTANT NOTES

1. The software is unfinished (in that there were many more features and settings planned) however completely functional as is
2. The design was not built responsively as this was not considered necessary for its usage; it was designed to fill up half a screen on 1920*1080 resolution
3. The PHP is largely procedural and contains lots of ugly copy & pasting. If I could go back and redo it now I'd make better use of OOP, e.g. a tenant class containing all possible info associated with tenants currently accessed through queries, such as rent balance, current property etc, so this info could be accessed easily anywhere in the app and new reports created with ease
4. Same goes for the CSS, which although split into sections is horribly structured and could use a bit of SASS (even without pre-processing I could probably get it about a third of the size it is now with better structuring)
5. The app makes use of a 'Secure Login' tutorial plugin to prevent unauthorised access, which is pretty comprehensive but if I were to redo it now I'd probably look at encasing the whole thing in a framework (complete with front controller structure)
6. It has only ever been me working on the app, so the comments are for my own sake and might not make much sense to others (I haven't thoroughly checked over them so there may be some downright weird ones... I apologise in advance)

Rich
