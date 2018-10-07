# Internal

Internal documentation for project maintainers

## Create a release

- Prepare a GitHub release-draft
- Change .github_changelog_generator config accordingly to new release tag (increase future release)
- Change version constants in `src/Constants.php`
- Create CHANGELOG.md with [github-changelog-generator](https://github.com/github-changelog-generator/github-changelog-generator]) by running `github_changelog_generator kevinpapst/kimai2`
- Edit the release-draft and add the "Full changelog" link + everything from CHANGELOG.md related to the new version 
- Push a release branch and merge it as last PR into master
- Create the release
- Post a new issue at [YunoHost tracker for Kimai 2](https://github.com/YunoHost-Apps/kimai2_ynh)
