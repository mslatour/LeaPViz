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
The purpose of a data source is to serve as an abstraction of the actual data source, so that the rest of the application doesn't need to know anything about the specific data source used for a particular visualisation or how to interact with that. In a sense the data source works as an API for the actual data source (like a SQL database, triple store or XML document). In this project only one specific source has been fully supported in the context of this project, however the idea holds that one could use various data sources for this without having to change data structures and views.

