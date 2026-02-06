# sports-king

Common Library for sports.

スポーツ系サイト向けの共通ライブラリです。
このライブラリは Packagist 経由で配布されています。
- https://packagist.org/packages/tarosky/sports-king


## 使用方法

このライブラリを使用するときは、composer.json に下記のように記述してください。
Composer が解決するバージョンは、基本的に **Gitのタグ**（例: `0.9.7`）が基準です。


```
"require": {
    "tarosky/sports-king": "^0.9.7"
}
```

## 開発者向け

### リリース手順

main ブランチに変更があった場合は、次の手順を踏むことで最新バージョンをリリースできます。

1. main ブランチにプルリクエストをマージしてください。
2. [Releases](https://github.com/tarosky/sports-king/releases) にリリースノートのドラフトが自動で作成されます。
3. リリースノートのドラフトの内容を確認してください。
   1. 特に、リリースノートのタイトルとタグが適切な最新バージョンとなっているか確認してください。
   2. 必要であれば、タイトル右横の編集ボタンから手動で修正してください。
4. 問題がなければ、リリースノートのドラフトを公開してください。

#### 補足

- main ブランチに変更が push されると、GitHub Actions に設定している [release-drafter](https://github.com/release-drafter/release-drafter) によって [Releases](https://github.com/tarosky/sports-king/releases) にリリースノートのドラフトが自動で作成されます
- リリースノートのドラフトが自動作成されるとき、既にドラフトがある場合は既存のドラフトが更新されます。
- Packagist は リリースノートではなくタグからバージョンを取得しています。
  - リリースノート公開時にタグを新規作成することで、新バージョンとして Packagist に反映されます。
