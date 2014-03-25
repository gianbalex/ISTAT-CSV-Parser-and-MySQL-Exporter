ISTAT CSV Parser and MySQL Exporter

Copyright (c) 2014 Gianfilippo Balestriero
see LICENSE.txt

Usage:

1) Go to http://www.istat.it/it/archivio/6789
2) Download and extract "Elenco comuni italiani (xls-csv)" and "Ripartizioni, province, regioni (xls-csv)".
3) Put the "elenco_comuni_italiani_[NUM].csv" file in "csv" folder then rename it in "comuni.csv";
4) Put the "ripartizioni_regioni_province_[num].csv" file in "csv" folder then rename it in "regioni_province.csv";
5) Run "php istat_parser.php" command from your command console or execute it on your web server
6) Will be created two files in "export" folder: "istat_parser_export.sql" and "istat_parse_export.gz".
7) Import one of this files on your MySQL database or by PhpMyAdmin.

It will create the following table structure with the following relations:

istat_regioni:
	codice(primary)
	nome

istat_province:
	codice(primary)
	codice_regione 		-> istat_regioni[codice]
	nome

istat_comuni:
	codice(primary)
	codice_regione 		-> istat_regioni[codice]
	codice_provincia 	-> istat_province[codice]

Important:

	Set "utf8_general_ci" colletion for your ISTAT database