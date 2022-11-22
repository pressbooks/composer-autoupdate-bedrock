# Composer auto update

This action triggers a composer update on a given branch and creates a pull request with the changes.

## Inputs

### `triggered-by`

**Required** The repo that triggers the action.

## Example usage

```yaml
uses: actions/composer-autoupdate-bedrock@v1.0
with:
  triggered-by: ${{ github.repository }}
```
