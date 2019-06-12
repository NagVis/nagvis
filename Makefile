SHELL=/bin/bash
VERSION=1.9.13
NAME=nagvis-$(VERSION)

SED ?= sed
ifeq ($(shell uname), Darwin)
    SED := gsed
endif

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
	cp $(NAME).tar.gz ~/git/nagvis.org/htdocs/share/
	VERSION=$(NAME) $(MAKE) -C ~/git/nagvis.org/htdocs release

release: dist create-tag publish version

version:
	@newversion=$$(dialog --stdout --inputbox "Next Version:" 0 0 "$(VERSION)") ; \
	if [ -n "$$newversion" ] ; then $(MAKE) NEW_VERSION=$$newversion setversion ; fi

setversion:
	$(SED) -i "s/^VERSION=.*/VERSION=$(NEW_VERSION)/g" Makefile
	$(SED) -i "s/.*CONST_VERSION.*/define('CONST_VERSION', '$(NEW_VERSION)');/g" share/server/core/defines/global.php
	$(SED) -i '1s;^;$(NEW_VERSION)\n\n;' ChangeLog
	MAJ_VERSION=$(NEW_VERSION) ; MAJ_VERSION=$${MAJ_VERSION::3} ; \
	$(SED) -i "s/<title>NagVis [^ ]*/<title>NagVis $$MAJ_VERSION/g" docs/*/index.html ; \
	$(SED) -i "s/<h1>NagVis [^ ]*/<h1>NagVis $$MAJ_VERSION/g" docs/*/welcome.html ; \
	$(SED) -i "s/: [0-9.]*\.x/: $$MAJ_VERSION.x/g" docs/*/welcome.html ; \
	$(SED) -i "s/>Version [^ ]*/>Version $$MAJ_VERSION/g" docs/*/toc.html

update-copyright:
	for F in $$(find . -name *.php -o -name *.js -o -name \*.sh); do \
	    $(SED) -i -r "s/Copyright \(c\) 2004-[0-9]{4} NagVis Project \(Contact: info@nagvis.org\)/Copyright (c) 2004-$$(date +%Y) NagVis Project (Contact: info@nagvis.org)/g" $$F ; \
	done
	$(SED) -i "s/Copyright &copy; 2008-[0-9]*/Copyright \&copy; 2008-$$(date +%Y)/g" docs/*/welcome.html

doc-cleanup:
	find docs/* -name *.html -exec $(SED) -ri \
	    's% ?\(?<font color="(#ff0000|red)">\(?(New|Neu|neu) in 1\.[567]\.?.?\)?:?</font>\)?%%g' {} \;

localize: localize-sniff localize-compile

localize-sniff:
	@if ! type xgettext >/dev/null 2>&1; then \
	    echo "Please install xgettext" ; \
	    exit 1 ; \
	fi
	@if [ -z $(LANG) ]; then \
	    echo "Please set LANG=[locale] e.g. LANG=en_US" ; \
	    exit 1 ; \
	fi
	PO_FILE=share/frontend/nagvis-js/locale/$(LANG)/LC_MESSAGES/nagvis.po ; \
	MO_FILE=share/frontend/nagvis-js/locale/$(LANG)/LC_MESSAGES/nagvis.mo ; \
	$(SED) -i -e "/^#: /d" $$PO_FILE ; \
	xgettext --no-wrap --sort-output -j --keyword=l -L PHP --from-code=UTF-8 \
                 --foreign-user --package-version="" \
                 --package-name="NagVis $(VERSION)" \
                 --msgid-bugs-address=info\@nagvis.org \
                 -d "NagVis" -o $$PO_FILE `find . -type f | grep .php | xargs` ; \
	$(SED) -i -e "s/FULL NAME <EMAIL@ADDRESS>/Lars Michelsen <lm@larsmichelsen.com>/g" $$PO_FILE ; \
        $(SED) -i -e "s/CHARSET/utf-8/g" $$PO_FILE ; \
        $(SED) -i -e "s/LANGUAGE <LL@li\.org>/NagVis Team <info@nagvis.org>/g" $$PO_FILE ; \
        $(SED) -i -e "s/YEAR-MO-DA HO:MI+ZONE/$$(date +"%Y-%m-%d %H:%M%z")/g" $$PO_FILE ; \
        $(SED) -i -e "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR/Lars Michelsen <lm@larsmichelsen.com>, $$(date +%Y)/g" $$PO_FILE ; \
        $(SED) -i -e "s/SOME DESCRIPTIVE TITLE\./NagVis language file/g" $$PO_FILE ; \
        $(SED) -i -e "s/# This file is put in the public domain\./#/g" $$PO_FILE ; \
        $(SED) -i -e "s/\"Language: /\"Language: German/g" $$PO_FILE ; \
	echo "Updated file $$PO_FILE"

localize-compile:
	@if ! type msgfmt >/dev/null 2>&1; then \
            echo "Please install msgfmt" ; \
	    exit 1 ; \
	fi
	@PO_FILE=share/frontend/nagvis-js/locale/$(LANG)/LC_MESSAGES/nagvis.po ; \
	MO_FILE=share/frontend/nagvis-js/locale/$(LANG)/LC_MESSAGES/nagvis.mo ; \
	msgfmt $$PO_FILE -o $$MO_FILE ; \
	echo "Updated file $$MO_FILE"
