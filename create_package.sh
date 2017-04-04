#!/bin/bash
rsync -av --exclude=".*" --exclude="*.zip"  --exclude="*.pdf" --exclude="*.docx" --exclude="*.sh" . bluepayment
zip -ur bluepayment.zip bluepayment
rm -R bluepayment