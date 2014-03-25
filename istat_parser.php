<?php
/*
* ISTAT CSV Parser and MySQL Exporter
* 
* Copyright (c) 2014 Gianfilippo Balestriero <info@enterinjs.com>
* 
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/
header("Content-type: text/plain");
class Istat_Parser {

	var $file_regioni_province 	= 	"csv/regioni_province.csv";
	var $file_comuni 			= 	"csv/comuni.csv";

	var $file_name_sql 			= 	"./export/istat_parser_export.sql";
	var $file_name_gz 			= 	"./export/istat_parser_export.gz";	

	var $sql			=	"";

	var $arr_reg		=	array();
	var $arr_prov		=	array();
	var $arr_comuni		=	array();

	var $arr_positions		= 	array(
		"codice_regione" 		=> 5,
		"nome_regione"			=> 9,
		"codice_provincia"		=> 10,
		"nome_provincia"		=> 13,
		"sigla_provincia"		=> 14,
		"c_codice_regione"		=> 3,
		"c_codice_provincia"	=> 4,
		"c_codice_comune"		=> 6,
		"c_nome_comune"			=> 11,

	);

	var $regioni_count;
	var $province_count;
	var $comuni_count;

	function Istat_Parser(){
		$this->write("license");
		$this->files_control();
		$this->write("files_ok");
		$this->create_tables_structure();
		$this->truncate_tables();
		$this->process_regioni();
		$this->write("export_regioni_ok");
		$this->process_province();
		$this->write("export_province_ok");
		$this->process_comuni();
		$this->write("export_comuni_ok");
		$this->save_file();
		$this->write("done");
	}

	function files_control(){
		if(!file_exists($this->file_comuni)) 			die("[KO] ".$this->file_comuni. " file not exists\n\n");
		if(!file_exists($this->file_regioni_province)) 	die("[KO] ".$this->file_regioni_province." file not exists\n\n");
	}

	function create_tables_structure(){
		$this->sql.= "CREATE TABLE IF NOT EXISTS istat_regioni(codice int not null primary key, nome tinytext);\n";
		$this->sql.= "CREATE TABLE IF NOT EXISTS istat_province(codice int not null primary key, codice_regione int, nome tinytext, sigla tinytext );\n";
		$this->sql.= "CREATE TABLE IF NOT EXISTS istat_comuni(codice int not null primary key, codice_regione int, codice_provincia int, nome tinytext);\n\n";
	}

	function truncate_tables(){
		$this->sql.="TRUNCATE istat_regioni;\n";
		$this->sql.="TRUNCATE istat_province;\n";
		$this->sql.="TRUNCATE istat_comuni;\n\n";
	}

	function write($type){
		$txt = "";
		if($type == "license"){
			$txt = "\n".$this->read_license();
		}
		else if($type == "files_ok"){
			$txt="[OK] Files Exists";
		}
		else if($type == "export_regioni_ok"){
			$txt="[OK] Regions";
		}
		else if($type == "export_province_ok"){
			$txt="[OK] Provinces";
		}
		else if($type == "export_comuni_ok"){
			$txt="[OK] Municipalities";
		}
		else if($type == "done"){
			$txt="\nWere exported:\n\n";
			$txt.=$this->regioni_count." Regions\n";
			$txt.=$this->province_count." Provinces\n";
			$txt.=$this->comuni_count." Municipalities\n\n";
			$txt.="generated file:\n\t".$this->file_name_sql."\n";
			$txt.="\t".$this->file_name_gz."\n";
			$txt.="\nDone!";
		}								
		echo $txt."\n";

	}

	function process_regioni(){
		$arr_temp 	= $this->read_file($this->file_regioni_province, "regioni_province");
		$arr_unique = array();
		foreach($arr_temp as $arr_sep){
			if(!isset($arr_sep[$this->arr_positions["codice_regione"]])  ||  $arr_sep[$this->arr_positions["codice_regione"]] ==""){
				continue;
			}				
			$codice_regione = (int) $this->clean($arr_sep[$this->arr_positions["codice_regione"]]);
			$nome_regione	= $this->clean($arr_sep[$this->arr_positions["nome_regione"]]);
			$arr_unique[$codice_regione]	= $nome_regione;
		}
		foreach ($arr_unique as $codice_regione => $nome_regione) {
			$this->sql .= "INSERT INTO istat_regioni(codice, nome) VALUES('$codice_regione','$nome_regione');\n";
		}
		$this->sql.="\n";
		$this->regioni_count = count($arr_unique);
	}

	function process_province(){
		$arr_temp 	= $this->read_file($this->file_regioni_province, "regioni_province");
		$arr_unique = array();
		foreach($arr_temp as $arr_sep){
			if(!isset($arr_sep[$this->arr_positions["codice_provincia"]]) ||  $arr_sep[$this->arr_positions["codice_provincia"]] ==""){
				continue;
			}			
			$codice_provincia 				= (int) $this->clean($arr_sep[$this->arr_positions["codice_provincia"]]);
			$nome_provincia					= $this->clean($arr_sep[$this->arr_positions["nome_provincia"]]);
			$codice_regione					= (int) $this->clean($arr_sep[$this->arr_positions["codice_regione"]]);
			$sigla_provincia				= $this->clean($arr_sep[$this->arr_positions["sigla_provincia"]]);
			$arr_unique[$codice_provincia] 	= array(
				"codice_regione" 	=> $codice_regione,
				"nome_provincia" 	=> $nome_provincia,
				"sigla_provincia" 	=> $sigla_provincia
			);
		}
		ksort($arr_unique);
		foreach ($arr_unique as $codice_provincia => $arr_params) {
			$nome_provincia		= $arr_params["nome_provincia"];
			$codice_regione		= $arr_params["codice_regione"];
			$sigla_provincia	= $arr_params["sigla_provincia"];
			$this->sql 			.= "INSERT INTO istat_province(codice, codice_regione, nome, sigla) VALUES('$codice_provincia','$codice_regione','$nome_provincia', '$sigla_provincia');\n";				
		}
		$this->sql.="\n";
		$this->province_count = count($arr_unique);
	}

	function process_comuni(){
		$arr_temp 	= $this->read_file($this->file_comuni, "comuni");
		$arr_unique = array();
		$i = 1;
		foreach($arr_temp as $arr_sep){
			if(!isset($arr_sep[$this->arr_positions["c_codice_regione"]]) || $arr_sep[$this->arr_positions["c_codice_regione"]] ==""){
				continue;
			}
			$codice_regione 	= (int) $this->clean($this->parse_int($arr_sep[$this->arr_positions["c_codice_regione"]]));
			$codice_provincia	= (int) $this->clean($this->parse_int($arr_sep[$this->arr_positions["c_codice_provincia"]]));
			$codice_comune		= $i;
			$nome_comune		= $this->clean($arr_sep[$this->arr_positions["c_nome_comune"]]);

			$arr_unique[$codice_comune] = array(
				"codice_regione" 	=> $codice_regione,
				"codice_provincia" 	=> $codice_provincia,
				"nome_comune" 		=> $nome_comune
			);

			$i++;

		}
		ksort($arr_unique);
		foreach ($arr_unique as $codice_comune => $arr_params) {
			$codice_regione		= $arr_params["codice_regione"];
			$codice_provincia	= $arr_params["codice_provincia"];
			$nome_comune		= $arr_params["nome_comune"];
			$this->sql 			.= "INSERT INTO istat_comuni(codice, codice_regione, codice_provincia, nome) VALUES('$codice_comune','$codice_regione','$codice_provincia','$nome_comune');\n";
		}
		$this->sql.="\n";
		$this->comuni_count = count($arr_unique);
	}

	function save_file(){
		$license = $this->read_license();
		@unlink($this->file_name_sql);
		@unlink($this->file_name_gz);
		file_put_contents($this->file_name_sql, $license.$this->sql);
		$gzip = gzencode($license.$this->sql);
		file_put_contents($this->file_name_gz, $gzip);
	}

	function read_license(){
		$license_raw = file_get_contents("LICENSE.txt");
		$license 	 = "/*\n";
		$arr_exp = explode("\n", $license_raw);
		foreach ($arr_exp as $line) {
			$license .= "* ".$line."\n";
		}
		$license 	 .= "*/\n\n";
		return $license;
	}

	function read_file($file, $type){
		if($type == "regioni_province"){
			$offset = 3;
		}
		if($type == "comuni"){
			$offset = 5;
		}		
		$file_raw 	= file_get_contents($file);
		$file_raw 	= str_replace("\r", "", $file_raw);
		$arr_exp 	= explode("\n", $file_raw);
		$arr_exp 	= array_slice($arr_exp, $offset);
		$arr_temp 	= array();
		foreach($arr_exp as $line) {
			if($line==""){
				break;
			}
			$arr_exp_sep = explode(";", $line);
			$arr_temp[] = $arr_exp_sep;
		}
		return $arr_temp;
	}

	function format_codice_comune($codice){
		return ($codice-1000);
	}

	function parse_int($num){
		if(substr($num,0,2) =="00"){
			$num = substr($num, 2,strlen($num));
		}		
		else if(substr($num,0,1) =="0"){
			$num = substr($num, 1, strlen($num));
		}		
		return $num;
	}

	function clean($str){
		if(strpos($str, "/")){
			$str = reset(explode("/", $str));
		}
		$str = utf8_encode(mysql_real_escape_string(trim($str)));
		return $str;
	}
}

new Istat_Parser();