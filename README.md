# Integrates Piwik in the TYPO3 Backend

![Build Status](https://travis-ci.org/kaystrobach/TYPO3.piwikintegration.svg)

See why the build is currently failing, most of the issues are CGL things, will be fixed soon.

https://travis-ci.org/kaystrobach/TYPO3.piwikintegration/

# Updating

## to 5.x future

* major api changes due to switch to namespaces

## to 4.x

Due to the switch to extbase these 2 things has to be taken into account

* Please recheck that the static template of piwikintegration is still included after upgrading to 4.x
* If the BE module of piwikintegration had been made accessible for BE users or groups, then it will now no longer be accessible and it must be made accessible in the BE user/group record again. 
