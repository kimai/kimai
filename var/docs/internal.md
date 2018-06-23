# Internal

Internal documentation for project maintainers

## Create a release

- Prepare a GitHub release-draft
- Change .github_changelog_generator config accordingly to new release tag
- Create CHANGELOG.md with [github-changelog-generator](https://github.com/github-changelog-generator/github-changelog-generator]) by running `github_changelog_generator kevinpapst/kimai2`
- Push a release branch and add it as last PR merge into master
- Create the release
