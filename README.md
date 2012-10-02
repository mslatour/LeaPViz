LeaPViz (Learning Path Visualization)
====================================

Introduction
------------
The LeaPViz tool was built to visualise data trails of users in an online environment that provides information via various resources. The application for which this tools was created was an online course. The work on this tool was done in the innovation project 'User needs van docent en student bij de inzet van learning analytics' conducted by the University of Amsterdam and the Free University in Amsterdam and was funded by the SURF Foundation in the tender focused around Learning Analytics.

The tool is generic in nature in the sense that it can easily be used for other visualisations and other domains, however the focus in the development has been on completing the project deliverables and the code is therefore not always optimal for the general case.

Main components
---------------
The framework consists of several main entities:

1. Data sources (Used to retrieve data from some source, like a database)
2. Data structures (Used to structure and re-structure the data)
3. Data views (Used to display the data, typically something that can be used in HTML)
4. UI elements (General purpose UI elements to combine and layout multiple data views)
5. Views (Used to join various UI elements on one page)

### Data sources ###
The purpose of a data source is to serve as an abstraction of the actual data source, so that the rest of the application doesn't need to know anything about the specific data source used for a particular visualisation or how to interact with that. In a sense the data source works as an API for the actual data source (like a SQL database, triple store or XML document). In this project only one specific source has been fully supported in the context of this project, however the idea holds that one could use various data sources for this without having to change data structures and views. The usage of abstract classes that define the API methods ensures that this is possible.

## Data structures ###
The purpose of a data structure is two-fold. First of all it provides an API to access certain parts of the raw data and retrieve properties like the size of the data without having to work with the actual raw array. Second of all, and perhaps more importantly, a data structure provides a mean to re-structure the data. A common application for this in this context is to transform a raw table that was retrieved from a data source into a two-dimensional matrix where both dimensions are taken from a column in the raw table. An other used transformation is a more low-level one where you need to resulting multi-dimensional array to have the dimensions in a different order (basically a transpose of the matrix). Typically the ideal order of dimensions depends on the operation that will be performed on the resulting array, and it is not unlikely that the data needs to be re-structured several times in order to make operations more efficient or easy. In a way, data structures allow you to look at your data from various viewpoints. The focus in this project was mainly on these types of data structures (and these types of transformations), but many more structures and transformations are imaginable (even non-array based structures, but that is left out of the scope for the moment). After all transformations needed have been performed on the raw data, the structured data array is typically extracted and used in a data view.
