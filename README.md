# Search-Engine-for-News-Website
- The aim of this project is to design a search engine for a news website and compare the search results of 2 page ranking algorithms
- A set of HTML documents corresponding to the news website is provided
- These documents are indexed using Apache Solr's built-in post tool that leverages Tika library
- Apache Solr has a default page ranking algorithm (Lucene). It also allows to implement an external ranking algorithm
- This algorithm is based on the number of incoming and outgoing links from an HTML page within the domain of the news website
- This calculation is done using Java's Jsoup library
- This information is further used to construct a graph and calculate the page ranks for each page using Python's NetworkX library
- Apache Solr's default autosuggest feature is implemented in addition to autocorrect feature that suggests correct query terms for queries that are incorrect up to 2 edit distances
- The code that communicates with the browser on front-end and the Apache Solr server on the back end is implemented using PHP
- The user can search for a query term in the provided search box and select a page ranking algorithm to be implemented
- The result is a set of top 10 search results containing url to the webpage related to the query term
- Refer to project_description folder for implementation details

Technologies used:
-
- Languages: Java, Python, PHP
- Web technologies: HTML, CSS, jQuery
- Apache Solr
- Environment: UNIX
