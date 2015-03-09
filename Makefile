SHELL=/bin/bash
VERSION=1.8.1
NAME=nagvis-$(VERSION)

help:
	@echo "This Makefile is meant to support development. Not useful for installing NagVis."
	@echo
	@echo "  dist             - Build the release archive"
	@echo "  release          - Build the archive, create tag and publish it"
	@echo "  version          - Update the version in all relevant files"
	@echo "  update-copyright - Set copyright info to current year"
	@echo "  doc-cleanup      - Remove old "new in version X" notes from documentation"
	@echo

dist:
	git archive --format=tar --prefix=$(NAME)/ HEAD | gzip > $(NAME).tar.gz

create-tag:
	V=$(VERSION) ; if [ $${#V} -eq 3 ]; then \
	    TAG=nagvis-$$V.0 ; \
	else \
	    TAG=nagvis-$$V ; \
	fi ; \
	git tag -a -m "Tag for NagVis $$V release" $$TAG ; \
	git push origin --tags

copy-to-website:

publish:
	cp $(NAME).tar.gz /d1/lm/nagvis.org/share
	VERSION=$(NAME) $(MAKE) -C /d1/lm/nagvis.org/htdocs release

release: dist create-tag publish

version:
	@newversion=$$(dialog --stdout --inputbox "New Version:" 0 0 "$(VERSION)") ; \
	if [ -n "$$newversion" ] ; then $(MAKE) NEW_VERSION=$$newversion setversion ; fi

setversion:
	sed -ri 's/^(VERSION[[:space:]]*= *).*/\1'"$(NEW_VERSION)/" Makefile
	sed -i "s/.*CONST_VERSION.*/define('CONST_VERSION', '$(NEW_VERSION)');/g" share/server/core/defines/global.php
	MAJ_VERSION=$(NEW_VERSION) ; MAJ_VERSION=$${MAJ_VERSION::3} ; \
	sed -i "s/<title>NagVis [^ ]*/<title>NagVis $$MAJ_VERSION/g" docs/*/index.html ; \
	sed -i "s/<h1>NagVis [^ ]*/<h1>NagVis $$MAJ_VERSION/g" docs/*/welcome.html ; \
	sed -i "s/: [0-9.]*\.x/: $$MAJ_VERSION.x/g" docs/*/welcome.html ; \
	sed -i "s/>Version [^ ]*/>Version $$MAJ_VERSION/g" docs/*/toc.html

update-copyright:
	for F in $$(find . -name *.php -o -name *.js -o -name \*.sh); do \
	    sed -i -r "s/Copyright \(c\) 2004-[0-9]{4} NagVis Project \(Contact: info@nagvis.org\)/Copyright (c) 2004-$$(date +%Y) NagVis Project (Contact: info@nagvis.org)/g" $$F ; \
	done
	sed -i "s/Copyright &copy; 2008-[0-9]*/Copyright \&copy; 2008-$$(date +%Y)/g" docs/*/welcome.html

doc-cleanup:
	find docs/* -name *.html -exec sed -ri \
	    's% ?\(?<font color="(#ff0000|red)">\(?(New|Neu|neu) in 1\.[567]\.?.?\)?:?</font>\)?%%g' {} \;
