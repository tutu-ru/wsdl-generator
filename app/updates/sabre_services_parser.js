/**
 HOW TO USE
 
 1. Go to http://andreypolyak.github.io/sabre-apis-ru/services.html
 2. Open console and insert script below
 3. Run script
 
 Remember that this list may not contain actualised versions
 Is better to find a way to get versions from official docs 
*/


var t = '[\n';
$('table tr:gt(0)').each(function(){
	t += '\t[\n';

	var serviceCell = $(this).find('td:eq(0)');
	var s = $(serviceCell).find('a').text();
	var v = $(serviceCell).find('em').text().replace(/\(/g,'').replace(/\)/g,'');
	var o = $(serviceCell).find('a').attr('href');
	t += '\t\t\'service\' =>\''+ s +'\',\n';
	t += '\t\t\'version\' =>\''+ v +'\',\n';
	t += '\t\t\'documentation\' =>\''+ o +'\',\n';
	var wsdlCell = $(this).find('td:eq(2)');
	var w = $(wsdlCell).find('a:last').attr('href');
	t += '\t\t\'wsdl\' =>\''+ w +'\',\n';
	var descriptionCell = $(this).find('td:eq(1)');
	var d = $(descriptionCell).text();
	t += '\t\t\'description\' =>\''+ d +'\',\n';

	t+='\t],\n';
});
t+='];\n';

console.log(t);