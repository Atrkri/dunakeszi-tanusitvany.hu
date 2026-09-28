<?php
foreach($required_fields as $field => $field_name){
	$required_fields[$field] = test_input($_POST[$field_name]);
}
foreach($optional_fields as $field => $field_name){
	$optional_fields[$field] = test_input($_POST[$field_name]);
}

// Validáció
$invalid_values = array(
	'Ã¡', // á
	'Ã©', // é
	'Ã­', // í
	'Ã³', // ó
	'Ã¶', // ö
	'Ãº', // ú
	'Ã¼', // ü
	'&lt;a', // <a
	'&lt;p', // <p
	'&lt;b', // <b
	'&lt;h1', // <h1
	'&lt;h2', // <h2
	'&lt;h3', // <h3
	'&lt;h4', // <h4
	'&lt;h5', // <h5
	'&lt;h6', // <h6
	'&lt;div', // <div
	'&lt;img', // <img
	'&lt;form', // <form
	'&lt;label', // <label
	'&lt;input', // <input
	'&lt;button', // <button
	'http://',
	'https://',
);
foreach($required_fields as $field => $field_name){
	if($field_name == '' || $field_name == '0'){
		$required_fields[$field] = 'invalid';
	}else{
		foreach($invalid_values as $value){
			if(strpos($field_name, $value) !== false){
				$required_fields[$field] = 'invalid';
			}
		}
	}
}
foreach($optional_fields as $field => $field_name){
	if($field_name != '' && $field_name != '0'){
		foreach($invalid_values as $value){
			if(strpos($field_name, $value) !== false){
				$optional_fields[$field] = 'invalid';
			}
		}
	}
}
if(!preg_match('/[a-záéíóöőúüűA-ZÁÉÍÓÖŐÚÜŰ0-9]{2,} .*[a-záéíóöőúüűA-ZÁÉÍÓÖŐÚÜŰ0-9]{2,}/', $required_fields['Name'])){
	$required_fields['Name'] = 'invalid';
}
if(!filter_var($required_fields['Email'], FILTER_VALIDATE_EMAIL)){
	$required_fields['Email'] = 'invalid';
}
$words = explode(' ', $required_fields['Name']);
$longestWordLength = 0;
foreach ($words as $word) {
	if (strlen($word) > $longestWordLength) {
		$longestWordLength = strlen($word);
	}
}
if ($longestWordLength > 30) {
	$required_fields['Name'] = 'invalid';
}

extract($required_fields);
extract($optional_fields);
?>