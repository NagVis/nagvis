#!/bin/bash

find -name *.html -exec sed -r -i 's% ?\(?<font color="(#ff0000|red)">\(?(New|Neu|neu) in 1\.5\.?.?\)?:?</font>\)?%%g' {} \;
