# 🥤 Vending Machine MQTT Simulator (ThingSpeak + Tkinter)

Simulação gráfica de uma **máquina de vendas (Vending Machine)** desenvolvida em **Python + Tkinter**, que recebe comandos remotamente via **MQTT** utilizando o broker do **ThingSpeak**.

O sistema representa visualmente uma vending machine com **16 produtos (grid 4x4)**.  
Quando um comando MQTT é recebido, o produto correspondente é liberado na interface gráfica, simulando o comportamento físico de uma máquina real baseada em IoT.

---

## 📌 Visão Geral

Este projeto faz parte de uma arquitetura IoT onde:

- Um sistema web ou dispositivo envia dados para o **ThingSpeak**
- O ThingSpeak publica os dados via **MQTT**
- A aplicação Python recebe o comando
- A interface gráfica simula a liberação do produto

A aplicação funciona como um **dashboard visual / digital twin** da vending machine física.

---

<img width="600" alt="Captura de tela de 2026-02-20 21-48-58" src="https://github.com/user-attachments/assets/f5106388-15aa-410f-87b3-fe36600ef920" />

## 🧠 Arquitetura do Sistema

Web System / API  
→ ThingSpeak Channel (REST Update)  
→ MQTT Broker ThingSpeak  
→ Python MQTT Client  
→ Interface Tkinter (Simulação Visual)

O campo `field1` do canal ThingSpeak representa o **ID do produto** a ser liberado.

---

## ⚙️ Tecnologias Utilizadas

Python 3.x

Tkinter (Interface gráfica)

paho-mqtt (Cliente MQTT)

ThingSpeak MQTT Broker

---

## 📂 Estrutura de arquivos 

```
vending-machine-mqtt-simulator/
├── README.md
│
├── vmachine.py
├── requirements.txt
│
├── img/
│ ├── 101.png
│ ├── 102.png
│ ├── ...
│ └── 116.png
│
└── docs/
└── architecture.png
```


---

## 🖼️ Interface

- Grid 4x4 com 16 produtos
- Display estilo LED indicando status
- Animação de vibração simulando liberação
- Reposição automática do produto

Estados exibidos:

AGUARDANDO PEDIDO...  
PRODUTO X LIBERADO  
CÓDIGO INVÁLIDO  
ERRO DE CONEXÃO

---

## 🔌 Configuração MQTT (ThingSpeak)

A aplicação conecta ao broker MQTT do ThingSpeak utilizando autenticação.

Servidor MQTT:

mqtt3.thingspeak.com

Tópico utilizado:

channels/{CHANNEL_ID}/subscribe/fields/field1

O valor recebido deve ser um número entre **1 e 16**, correspondente ao produto.

Exemplo de mensagem MQTT:
Resultado:
Produto 5 é liberado na interface.

---

## 🚀 Instalação

### 1️⃣ Clonar o repositório

```bash
git clone https://github.com/seuusuario/vending-machine-mqtt-simulator.git
cd vending-machine-mqtt-simulator
```

## ✅ Requisitos do Sistema (Ubuntu)

Este projeto foi testado e pensado para rodar em **Ubuntu** (Desktop ou Server) usando **Python 3** e uma interface gráfica via **Tkinter**, além de conexão MQTT com **ThingSpeak** através da biblioteca **paho-mqtt**.

---

## 🧰 Ferramentas Necessárias no Ubuntu

### 1) Python 3 (interpretador principal)
O Python é o ambiente de execução do código (`main.py`). No Ubuntu, normalmente já vem instalado, mas é importante garantir que você tem o **Python 3.x** e o **pip** disponíveis.

**Verificar versões instaladas:**
```bash
python3 --version
pip3 --version

sudo apt update
sudo apt install -y python3 python3-pip
```

### 2) Tkinter (interface gráfica)

O Tkinter é a biblioteca padrão de GUI do Python e é usada para desenhar o grid 4x4 dos produtos e o display LED do dashboard.
No Ubuntu, o Tkinter pode não vir instalado por padrão.

Instalar Tkinter para Python 3:

```bash
sudo apt update
sudo apt install -y python3-tk
```

#### 3) Paho-MQTT (cliente MQTT)

A biblioteca paho-mqtt é utilizada para conectar no broker MQTT do ThingSpeak e assinar o tópico do field1, recebendo o número do produto a liberar.

Você pode instalar de duas formas: via APT (recomendado no Ubuntu quando você não usa venv) ou via pip (quando o sistema permite).

✅ Opção A (Recomendado no Ubuntu): instalar via APT

Evita o erro externally-managed-environment (comum no Ubuntu/Debian modernos).
```bash
sudo apt update
sudo apt install -y python3-paho-mqtt
```
## ▶️ Executar o Projeto no Ubuntu

Dentro da pasta do projeto, rode:

```bash
python3 vmachine.py
```
Se o MQTT conectar corretamente, o terminal deve mostrar algo como:

- Conectado ao ThingSpeak com código 0

A interface deve abrir com:

- grid 4x4 dos produtos (com imagens se existirem na pasta img/)

- display LED “AGUARDANDO PEDIDO...”

### 📡 Como o MQTT está configurado no código

O código se conecta ao broker MQTT do ThingSpeak:
- Broker: mqtt3.thingspeak.com
- Porta: 1883
  
Tópico:

- channels/<CHANNEL_ID>/subscribe/fields/field1
  
O valor do field1 deve ser um número de 1 a 16, representando o produto.

### 🖼️ Requisitos das Imagens (pasta img/)

O projeto tenta carregar imagens nesta convenção:

- Produto 1 → img/101.png

- Produto 2 → img/102.png

...

- Produto 16 → img/116.png

Se a imagem não existir, o sistema desenha um texto no lugar (PRODUTO X).

### 🧩 Observações Importantes

- Este projeto usa Tkinter, então precisa de ambiente gráfico (Ubuntu com GUI).

- Se você estiver rodando em servidor sem tela, você precisará:

   - ou rodar via X11 forwarding (SSH -X)

   - ou adaptar o projeto para modo “sem interface” (somente logs no terminal)

<img width="800" alt="Captura de tela de 2026-02-20 21-59-11" src="https://github.com/user-attachments/assets/63d4bf33-3a04-4503-a471-3878054d3362" />
