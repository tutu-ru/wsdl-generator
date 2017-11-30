/**
 HOW TO USE
 
 1. Go to http://andreypolyak.github.io/sabre-apis-ru/services.html
 2. Open console and insert script below
 3. Run script
 
 Remember that this list may not contain actualised versions
 Is better to find a way to get versions from official docs 
*/


var t = '[\n';
$('table tr').each(function(){
	t += '\t[\n';
	$(this).find('td:eq(0)').each(function(){
		var s = $(this).find('a').text();
		var v = $(this).find('em').text().replace(/\(/g,'').replace(/\)/g,'');
		t += '\t\t\'service\' =>\''+ s +'\',\n';
		t += '\t\t\'version\' =>\''+ v +'\',\n';
	});
	$(this).find('td:eq(2)').each(function(){
		var w = $(this).find('a:last').attr('href');
		t += '\t\t\'wsdl\' =>\''+ w +'\',\n';
	});
	t+='\t],\n';
});
t+='];\n';

console.log(t);