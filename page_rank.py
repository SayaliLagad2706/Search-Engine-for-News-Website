import networkx as nx
import math

G = nx.read_edgelist("output.txt", create_using=nx.DiGraph())

page_rank = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight', dangling=None)
# print(page_rank)
count = 0
with open("external_pageRankFile.txt", "w") as f:
	for pageid in page_rank:
		# print(count)
		# count = count+1
		f.write("/home/sayali/Downloads/solr-7.7.0/foxnews/"+pageid+"="+str(page_rank[pageid])+"\n")
f.close()
