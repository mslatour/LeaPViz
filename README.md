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
