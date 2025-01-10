## 🎉 Cardscript v0 发布

演示站点：[http://card.aiofchina.com](http://card.aiofchina.com)

**Cardscript** 是一个开源网站模板，旨在简化部署过程，适用于个人服务器。其主要特点包括：

### 🚀 主要功能

- **便捷部署**  
  轻松将模板部署到个人服务器，无需复杂配置。

- **卡片式界面**  
  采用卡片式设计，界面美观且用户友好。

- **一键脚本**  
  卡片包含一键脚本命令，点击复制图标即可复制命令，直接在服务器 SSH 界面粘贴并回车即可运行。

### 🌟 优势亮点

- **美观有趣**  
  精心设计的界面，提升用户体验。

- **方便实用**  
  简化操作步骤，提高部署效率，适合各种技术水平的用户。

### 📦 获取与安装

按照以下步骤在您的服务器上部署 **Cardscript v0**：

1. **下载压缩包**  
   在 [Releases](https://github.com/Vincentkeio/cardscript/releases) 页面下载最新的 `cardscript-v0.zip` 文件。

2. **使用宝塔面板新建网站**  
   - 登录您的宝塔面板。
   - 使用自己的域名新建一个网站。

3. **上传并解压压缩包**  
   - 进入新建网站的根目录。
   - 上传 `cardscript-v0.zip` 压缩包到根目录。
   - 在根目录下解压压缩包。
   - **注意**：如果解压后出现了一个 `cardscript v0` 文件夹，请进入该文件夹，将里面的所有文件和文件夹剪切，然后返回上一级目录（网站根目录），并将这些文件粘贴到根目录下，确保所有文件直接位于网站根目录中。

4. **配置网站**  
   - 在浏览器中输入您的域名，打开 **Cardscript** 初始化页面。
   - 页面将会提示数据库信息（数据库名、用户名、随机密码）。

5. **配置数据库**  
   - 在宝塔面板中，进入 **数据库** 界面。
   - 新建一个数据库，根据第4步中提示的信息填写数据库名、用户名和密码。
   - **编码** 请务必选择 `utf8mb4`。

6. **完成部署并访问网站**  
   - 部署完成后，打开浏览器访问您的域名，即可看到 **Cardscript** 网站首页。
   - 点击右上角的 **管理员登录** 按钮，使用初始用户名和密码 `sysadmin` 登录。
   - 登录后，建议您立即修改管理员密码以确保安全。

---

感谢您使用 **Cardscript v0**！如果有任何问题或建议，请在 [Issues](https://github.com/Vincentkeio/cardscript/issues) 中反馈。

---

### 📌 项目地址

访问我们的项目主页了解更多信息和最新动态：

🔗 [https://github.com/Vincentkeio/cardscript](https://github.com/Vincentkeio/cardscript)

---
