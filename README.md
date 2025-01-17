## 🎉 **Cardscript v0 发布**

🚀 **[演示站点](http://card.aiofchina.com)**

**Cardscript v0** 是一款轻量化、易部署的开源网站模板，能够在个人服务器上快速上线。无论您是新手还是有经验的开发者，都能轻松使用并进行定制。

### 🌐 部署环境

- **LNMP**：Nginx 1.26 + PHP 8.3 + MySQL 5.7

---

### 🚀 主要功能

- **便捷部署**  
  轻松将模板部署到个人服务器，无需复杂配置。

- **卡片式界面**  
  采用卡片式设计，界面美观且用户友好。

- **一键脚本**  
  卡片中包含一键脚本命令，点击复制图标即可复制命令，直接在服务器 SSH 界面粘贴并回车运行。

### 🌟 优势亮点

- **美观有趣**  
  精心设计的前端界面，让网站更具视觉吸引力。

- **方便实用**  
  减少部署与维护成本，适合各种技术水平的用户。

---

### 📦 获取与安装

按照以下步骤在您的服务器上部署 **Cardscript v0**：

1. **下载压缩包**  
   前往 [Releases](https://github.com/Vincentkeio/cardscript/releases) 页面下载最新的 `cardscript v0.zip` 文件。

2. **使用宝塔面板新建网站**  
   - 登录您的宝塔面板。
   - 使用您的域名新建一个网站。

3. **上传并解压压缩包**  
   - 进入新建网站的根目录。
   - 上传 `cardscript v0.zip` 压缩包到根目录。
   - 在根目录下解压压缩包。  
   - **注意**：若解压后出现 `cardscript v0` 文件夹，请进入该文件夹，将所有内容剪切并粘贴回网站根目录，确保所有文件直接位于根目录中。

4. **配置网站**  
   - 在浏览器中输入您的域名，打开 **Cardscript** 初始化页面。
   - 页面将提示输入数据库信息（数据库名、用户名、随机密码）。

5. **创建数据库**  
   - 在宝塔面板中，进入 **数据库** 界面。
   - 新建一个数据库，并根据第 4 步中提示的信息填写数据库名、用户名和密码。
   - **编码** 请务必选择 `utf8mb4`。

6. **完成部署并访问网站**  
   - 数据库创建完成后，再次访问您的域名，即可看到 **Cardscript** 网站首页。
   - 点击右上角的 **管理员登录** 按钮，使用初始用户名和密码 `sysadmin` 登录。
   - 登录后，建议立即修改管理员密码以确保安全。

---

### 📌 项目地址

访问我们的项目主页，获取更多信息与最新动态：

🔗 [https://github.com/Vincentkeio/cardscript](https://github.com/Vincentkeio/cardscript)

---

### 🖼️ 示例图

以下是一些示例页面截图：

![示例图1](https://github.com/Vincentkeio/cardscript/blob/main/1.png?raw=true)
![示例图2](https://github.com/Vincentkeio/cardscript/blob/main/2.png?raw=true)
![示例图3](https://github.com/Vincentkeio/cardscript/blob/main/3.png?raw=true)
![示例图4](https://github.com/Vincentkeio/cardscript/blob/main/4.png?raw=true)

---

### ❓ FAQ

1. **怎么更换壁纸？**  
   - 点击首页右上角，登录管理员面板（初始用户名与密码均为 `sysadmin`）。
   - 在“设置”页面找到壁纸 URL 选项，替换为您想使用的网络图片链接并保存。
   - 若想使用本地图片，可将图片命名为 `screen.png` 并上传到网站根目录覆盖原文件。

2. **怎么修改网站标题？**  
   - 登录管理员面板，进入“设置”页面。
   - 在“主标题”栏更改网站标题并保存。

3. **我安装完后，首页为什么是空的？**  
   - 需要在管理员面板中手动添加卡片后，首页才会显示相关内容。
   - 初始化状态没有默认卡片，所以首页会暂时空置。

---

🙏 感谢您使用 **Cardscript v0**！如有任何疑问或建议，欢迎在 [Issues](https://github.com/Vincentkeio/cardscript/issues) 中反馈。

---
