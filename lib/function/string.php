<?php

function str_trans($str) {
	return str_replace("'", "&#39;", str_replace("\"", "&quot;", $str));
}

function str_trans_rev($str) {
	return str_replace("&#39;", "'", str_replace("&quot;", "\"", $str));
}

// �ܺ� ��Ų�� ��� ��ȯ �Լ�.
// _t()�� ������ ������ ��������, _text()�� skin�� ����(��Ÿ����)�� ������. 1.1 ������ �߰�������.
function _text($t) {
	return $t;
}
?>
