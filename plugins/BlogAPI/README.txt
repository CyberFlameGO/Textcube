Blogger, MetaWeblog API for Tattertools.

(C) Copyright Hojin Choi, All right reserved.
You can distribute this program under GNU GPL license.

1. ���� ȯ�漳���� �÷����� �޴����� BlogAPI�� Ȱ��ȭ�Ͻʽÿ�.

2. ��α� ���� URL ������ġ�� ���� �� �ϳ��� �����Ͻʽÿ�.

	http://YOURDOMAIN/<TT-installpath>/plugin/BlogAPI
	http://YOURDOMAIN/<TT-installpath>/plugin/blogapi

	http://YOURDOMAIN/<TT-installpath>/plugin/BlogAPI/xmlrpc
	http://YOURDOMAIN/<TT-installpath>/plugin/blogapi/xmlrpc

	���� ������� ��� <TT-installpath> �� �ڽ��� ��θ� ��� �־� �ּž� �մϴ�.

3. �������� ��Ų�� �����ϸ� �ڵ����� api ��ġ�� �ڵ����� �νĽ�ų �� �ֽ��ϴ�.
	1.0.5 ���Ͽ����� �Ʒ� �±׸� ��Ų�� �����ʽÿ�.

<link rel="EditURI" type="application/rsd+xml" title="RSD" href="/<TT-installpath>/plugin/BlogAPI/rsd" />

	Zoundry������ Homepage���� �Է������� �ڵ����� xmlrpc ��θ� �ν��� �� �ֽ��ϴ�.

���:
1. Blogger API
2. MetaWeblog API
3. �׽�Ʈ�� Ŭ���̾�Ʈ: writely.com, zoundry, performancing
4. RSD(Really Simple Discovery) ����
5. ID alias �� �����մϴ�. .htaliases ���Ͽ� alias�� �����մϴ�.
	- FORMAT: <space>�� ����� ���� �ǹ��մϴ�.
		shortid<space>longid@youremail.com
		littleone<space>bigone@youremail.com

Versions:
----------------------------------------------------------------------------
* Version 0.9.6 (2006-07-02):
+ New
	- Entry url�� ���� ���� (�ҹ��� URL ����)
	- "Tatter Tools"�� Tattertools�� �ٲ�
	- .htaliases �� ���д� ���� ����
	- blogger api���� post �� return �Ǵ� id�� string���� ����.
	- TEST: Semagic 1.5.8.5 id�� �ٿ��� �����ϴ� ���

----------------------------------------------------------------------------
* Version 0.9.5 (2006-06-19):
+ New
	- ���� ����� ��� ����
	- 1.0.6 �̻󿡼� rsd ���� �±� �ڵ� ����. (Zoundry�� �׽�Ʈ��)
+ Change
	- bloggerapi.php�� blogger.php�� �̸� �ٲ�.
+ Fix
	- login ���� �޽����� PHP �����ڵ尡 �־� ����ε� XML�� ���۵��� �ʴ� ���� ����.
----------------------------------------------------------------------------
* Version 0.9.4 (2006-06-17):
+ New
	- �±� �� �з� ����
	- RSD(Really Simple Discovery) ����
+ Change
	- ���������� �÷��� �̺�Ʈ�� ����Ͽ� �����ϵ��� �ٲ�. 
+ Fix
	- ���丮���� ������ ���Ͽ� �������� �ʴ� ���׸� �ذ��մϴ�.

----------------------------------------------------------------------------
* Version 0.9.3 (2006-06-13):
+ New
	- MetaWeblog: metaWeblog.getCategories �߰���.
	- MetaWeblog: Performancing(firefox plugin)�� ���� content ���� ������ ����.
	- TEST: Performancing(firefox plugin)���� content ���� ������ �־� �׽�Ʈ.

----------------------------------------------------------------------------
* Version 0.9.2 (2006-06-13):
+ New
	- MetaWeblog API ���� (Writely.com,Zoundry���� �׽�Ʈ)
	- TEST: Writely.com: Category�� Tag�� ����Ͽ� ����.
	- TEST: Zoundry: Category�� �߰��� �� ����. (Zoundry�� Category�� TT�� �з��ΰ�?)
+ Change
	- Call/Response ��� ������� �����ϴ� XMLRPC Ŭ������ �̿���.
	- ���̻� class_path_parser.php �� �̿����� ����.
	- Debug file�� .ht �� �����ϵ��� ����

----------------------------------------------------------------------------
* Version 0.9.1 (2006-06-10):
+ New
	- �� ID�� ���Ͽ� alias�� �� �� ���� (.htaliases)
+ Change
	- Response�� ������� �����ϴ� XMLRPC Ŭ������ �̿���.
	  ���� xmlrpc ��û���� parsing�� class_path_parser.php �� �̿�.

----------------------------------------------------------------------------
* Version 0.9.0 (2006-06-06):
+ New
	- ���� ���� ����
	- Blogger API ����

----------------------------------------------------------------------------
----------------------------------------------------------------------------
----------------------------------------------------------------------------

(C) Copyright Hojin Choi, All right reserved.
You can distribute this program under GNU GPL license.

Author Email: hojin.choi@gmail.com
Home Page: http://coolengineer.com/

* You can use xmlrpc to post articles to Tattertools.

1. Enables BlogAPI plugin in your admin menu.
2. Specify plugin url to your blogging tool. as

	http://YOURDOMAIN/<TT-installpath>/plugin/BlogAPI
	http://YOURDOMAIN/<TT-installpath>/plugin/blogapi

	http://YOURDOMAIN/<TT-installpath>/plugin/BlogAPI/xmlrpc
	http://YOURDOMAIN/<TT-installpath>/plugin/blogapi/xmlrpc

	In multi-user environment <TT-installpath> can be owner's individual path.

3. You can add rsd link in your tatter skin to support automatic discover api.
	Add below tag in your skin html if you have one of version 1.0.5 or below.

<link rel="EditURI" type="application/rsd+xml" title="RSD" href="/<TT-installpath>/plugin/BlogAPI/rsd" />

	Zoundry tries to discover via your home url to get xmlrpc information.

Features:
1. Support Blogger API
2. Support MetaWeblog API
3. Tested in writely.com
   Tested in zoundry (http://www.zoundry.com/)
   Tested in performancing (http://performancing.com/)
4. Support RSD(Really Simple Discovery)
5. ID alias support, you can log in with other id, this feature helps you to use 
   blogging tools which restrict length of id.
   .htaliases file holds aliases and canonical ids.


----------------------------------------------------------------------------
