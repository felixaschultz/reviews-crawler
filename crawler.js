import puppeteer from "puppeteer";
import mysql from "mysql2/promise";

const connection = await mysql.createConnection({
    host: process.env.MYSQLI_HOST,
    user: process.env.MYSQLI_USER,
    password: process.env.MYSQLI_PASS,
    database: process.env.MYSQLI_DB,
    port: process.env.PORT,
});

try {
    const [rows, fields] = await connection.execute("SELECT * FROM api_keys");
    console.log(rows);
} catch (error) {
    console.error(error);
}

const browser = await puppeteer.launch({
    headless: true,
});

const page = await browser.newPage();
await page.goto("https://dk.trustpilot.com/review/cykelfaergen.info");
const reviews = await page.evaluate(() => {
    const reviews = [];
    document.querySelectorAll(".styles_reviewCardInner__EwDq2").forEach((review) => {
        reviews.push(review.innerHTML);
    });
    return reviews;
});

console.log(reviews);
await browser.close();