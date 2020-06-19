import com.opencsv.CSVReader;
import com.opencsv.exceptions.CsvValidationException;

import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.util.HashMap;
import java.util.HashSet;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;
public class ExtractUrls {

	public static void main(String[] args) throws IOException, CsvValidationException {
		// TODO Auto-generated method stub
		String fileName = "URLtoHTML_fox_news.csv";
		String htmlFileName = "/FOXNEWS/foxnews/";
		String outputPath = "output.txt";
		
		HashMap<String, String> idToUrlMap = new HashMap<String, String>();
		HashMap<String, String> urlToIdMap = new HashMap<String, String>();
		
			FileReader fileReader = new FileReader(fileName);
			CSVReader csvReader = new CSVReader(fileReader);
			String[] nextRecord;
			
			while((nextRecord = csvReader.readNext()) != null) {
				idToUrlMap.put(nextRecord[0],  nextRecord[1]);
				urlToIdMap.put(nextRecord[1], nextRecord[0]);
			}
			csvReader.close();
		
		File file = new File(htmlFileName);
		HashSet<String> links = new HashSet<String>();

		for(File f: file.listFiles()) {
			Document doc = Jsoup.parse(f, "UTF-8", idToUrlMap.get(f.getName()));
			Elements elem = doc.select("a[href]");
			for(Element e: elem) {
				String url = e.attr("abs:href").trim();
				if(urlToIdMap.containsKey(url)) {
					links.add(f.getName() + " " + urlToIdMap.get(url));
				}
			}	
		}

		FileWriter fileWriter = new FileWriter(outputPath, true);
		for(String s: links) {
			fileWriter.write(s);
			fileWriter.write("\r\n");
		}
		fileWriter.flush();
		fileWriter.close();
	}
}
