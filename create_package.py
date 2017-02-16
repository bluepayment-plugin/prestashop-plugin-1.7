#!/bin/bash
rsync -av --exclude=".*" --exclude="*.zip" --exclude="*.sh" . bluepayment
zip -ur bluepayment.zip bluepayment
rm -R bluepayment